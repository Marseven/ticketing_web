<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\EbillingBillState;
use App\Services\PaymentConfirmation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Vérifie auprès de la passerelle les paiements restés en attente.
 *
 * La notification d'e-billing se perd parfois : le client est débité, mais rien
 * n'arrive chez nous et son billet n'est jamais émis. Plutôt que d'attendre une
 * notification qui ne viendra pas, on va demander l'état de la facture — et on
 * encaisse par le même chemin que le webhook.
 *
 * Tourne toutes les cinq minutes (routes/console.php).
 */
class CheckPendingPayments extends Command
{
    protected $signature = 'payments:check-pending
        {--minutes=2 : ne regarder que les paiements initiés depuis au moins ce délai}
        {--hours=48 : ne pas remonter au-delà de cette ancienneté}
        {--limit=100 : nombre maximum de paiements interrogés}
        {--include-closed : interroger aussi les paiements dont la commande a été annulée}
        {--dry-run : lister sans rien encaisser}';

    protected $description = 'Interroge e-billing sur les paiements en attente et encaisse ceux qui sont réglés';

    public function handle(EbillingBillState $states, PaymentConfirmation $confirmation): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $payments = $this->pendingPayments();

        if ($payments->isEmpty()) {
            $this->info('Aucun paiement en attente à vérifier.');

            return self::SUCCESS;
        }

        $this->info("{$payments->count()} paiement(s) en attente — vérification auprès d'e-billing…");

        $settled = 0;
        $dead = 0;
        $stillWaiting = 0;
        $orphans = 0;

        foreach ($payments as $payment) {
            // En direct : on veut l'état courant de la facture, pas celui qui
            // avait été enregistré au moment d'une notification précédente.
            $state = $states->for($payment, preferStored: false);

            $orderStatus = $payment->order?->status;

            // En simulation, on montre CHAQUE facture et l'état rendu par la
            // passerelle. Sans cela, « toujours en attente » est indiscernable
            // de « la passerelle n'a pas répondu » ou de « aucun identifiant de
            // facture » — trois situations qui n'appellent pas la même suite.
            if ($dryRun) {
                $this->line(sprintf(
                    '  %-14s commande=%-10s %10s  facture=%s  état=%s',
                    $payment->order?->reference ?? '—',
                    $orderStatus ?? '—',
                    number_format((float) $payment->amount, 0, ',', ' '),
                    $states->billId($payment) ?: 'ABSENTE',
                    $state ?? 'SANS RÉPONSE — ' . ($states->lastFailure() ?? 'cause inconnue'),
                ));
            }

            // Aucun identifiant de facture : rien n'a jamais été créé chez la
            // passerelle, donc le client n'a rien pu régler et aucun appel ne
            // pourra jamais rien en dire. Passé le délai de rétention — le même
            // qui gouverne l'annulation des commandes — il n'y a plus rien à
            // attendre, que la commande soit close ou encore ouverte.
            //
            // On ne le fait pas plus tôt : dans la première heure, la création
            // de facture peut encore aboutir sur une seconde tentative.
            $expired = $payment->created_at?->lt(now()->subMinutes(Order::HOLD_MINUTES)) ?? true;

            if ($state === null && ! $states->billId($payment) && ($expired || $orderStatus !== 'pending')) {
                if (! $dryRun) {
                    $payment->update(['status' => 'failed']);
                }

                $dead++;
                continue;
            }

            if ($states->isPaid($state) && $orderStatus !== 'pending') {
                // Le client a payé APRÈS l'annulation de sa commande — la place
                // a pu être revendue entre-temps. Émettre un billet ici
                // survendrait la salle ; ne rien dire volerait le client. On
                // signale, et un humain tranche (billet ou remboursement).
                $this->error("  {$payment->order?->reference} : PAYÉ sur commande {$orderStatus}"
                    . " — {$payment->amount} — à traiter à la main");

                Log::critical('Paiement réglé sur une commande close', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->order?->reference,
                    'order_status' => $orderStatus,
                    'amount' => $payment->amount,
                    'ebilling_state' => $state,
                ]);

                if (! $dryRun) {
                    $payment->update(['ebilling_state' => $state]);
                }

                $orphans++;
                continue;
            }

            if ($states->isPaid($state)) {
                $this->line("  {$payment->order?->reference} : payé ({$state})");

                if (! $dryRun && $confirmation->confirm($payment, ['ebilling_state' => $state])) {
                    Log::info('Paiement récupéré par vérification périodique', [
                        'payment_id' => $payment->id,
                        'reference' => $payment->order?->reference,
                    ]);
                }

                $settled++;
                continue;
            }

            if ($states->isDead($state)) {
                // La facture ne sera pas réglée. On enregistre l'état sans
                // annuler : `CancelPendingOrders` libère la place à l'expiration,
                // et le client garde la possibilité de refaire un paiement.
                if (! $dryRun) {
                    $payment->update(['status' => 'failed', 'ebilling_state' => $state]);
                }

                $dead++;
                continue;
            }

            // Garder l'état observé : sans lui, la supervision ne peut pas
            // distinguer « facture abandonnée, sans conséquence » de « jamais
            // interrogée », et signalerait éternellement les deux.
            if (! $dryRun && $state !== null) {
                $payment->update(['ebilling_state' => $state]);
            }

            $stillWaiting++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[simulation] ' : '') . "Encaissés : {$settled} · Abandonnés : {$dead}"
            . " · Toujours en attente : {$stillWaiting} · À traiter à la main : {$orphans}");

        if ($orphans > 0) {
            $this->newLine();
            $this->error("⚠️  {$orphans} paiement(s) réglé(s) sur une commande annulée : ni billet ni remboursement.");
        }

        return self::SUCCESS;
    }

    /**
     * Paiements encore en cours, assez vieux pour que la notification normale
     * ait eu le temps d'arriver, et assez récents pour valoir un appel.
     */
    private function pendingPayments()
    {
        return Payment::query()
            ->with('order')
            ->where('status', 'initiated')
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('minutes')))
            ->where('created_at', '>=', now()->subHours((int) $this->option('hours')))
            ->when(
                ! $this->option('include-closed'),
                fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', 'pending')),
                fn ($q) => $q->whereHas('order'),
            )
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();
    }
}
