<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\EBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Réconciliation des commandes créditées à tort.
 *
 * Le webhook e-billing a longtemps considéré **toute notification reçue** comme
 * un paiement abouti, alors que la passerelle en émet aussi à la création de la
 * facture. Des commandes ont donc été marquées payées et des billets émis sans
 * que le client ait validé quoi que ce soit.
 *
 * Cette commande interroge la passerelle pour chaque commande payée et annule
 * celles dont la facture n'a jamais été réglée, ce qui relâche les places.
 * Par sécurité elle ne touche jamais une commande qu'elle n'a pas pu vérifier.
 */
class ReconcileEbillingPayments extends Command
{
    protected $signature = 'payments:reconcile-ebilling
        {--since= : ne regarder que les commandes passées depuis cette date (AAAA-MM-JJ)}
        {--event= : limiter à un événement (id ou slug)}
        {--dry-run : lister sans rien modifier}
        {--limit=500 : nombre maximum de commandes examinées}';

    protected $description = 'Annule les commandes créditées à tort par le webhook e-billing';

    /** États d'une facture qui valent paiement encaissé. */
    private const PAID_STATES = ['processed', 'paid'];

    public function handle(EBillingService $ebilling): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $payments = $this->paymentsToCheck();

        if ($payments->isEmpty()) {
            $this->info('Aucune commande payée à vérifier.');

            return self::SUCCESS;
        }

        $this->info("{$payments->count()} commande(s) payée(s) à vérifier auprès d'e-billing…");
        $this->newLine();

        $unpaid = [];
        $unverifiable = [];
        $confirmed = 0;

        foreach ($payments as $payment) {
            $state = $this->billState($ebilling, $payment);

            if ($state === null) {
                $unverifiable[] = $payment;
                continue;
            }

            if (in_array($state, self::PAID_STATES, true)) {
                $confirmed++;
                continue;
            }

            $unpaid[] = ['payment' => $payment, 'state' => $state];
        }

        $this->renderReport($confirmed, $unpaid, $unverifiable);

        if (empty($unpaid)) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Simulation : rien n\'a été modifié. Relancer sans --dry-run pour annuler ces commandes.');

            return self::SUCCESS;
        }

        $releasedSeats = 0;
        foreach ($unpaid as $row) {
            $releasedSeats += $this->cancelOrder($row['payment'], $row['state']);
        }

        $this->newLine();
        $this->info(count($unpaid) . ' commande(s) annulée(s), ' . $releasedSeats . ' place(s) relâchée(s).');

        return self::SUCCESS;
    }

    /**
     * Commandes payées dont le paiement est passé par e-billing.
     */
    private function paymentsToCheck()
    {
        $query = Payment::query()
            ->with('order.tickets')
            ->where('status', 'success')
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['paid', 'completed']));

        if ($since = $this->option('since')) {
            $query->whereHas('order', fn ($q) => $q->where('placed_at', '>=', $since));
        }

        if ($event = $this->option('event')) {
            $query->whereHas('order.tickets.event', function ($q) use ($event) {
                is_numeric($event) ? $q->where('events.id', $event) : $q->where('events.slug', $event);
            });
        }

        return $query->orderBy('id')->limit((int) $this->option('limit'))->get();
    }

    /**
     * État réel de la facture, ou null si la passerelle ne peut pas trancher —
     * auquel cas on ne touche à rien.
     */
    private function billState(EBillingService $ebilling, Payment $payment): ?string
    {
        if (! $payment->billing_id) {
            return null;
        }

        try {
            $result = $ebilling->getBillStatus((string) $payment->billing_id);
            $state = $result['bill_status'] ?? null;

            return is_string($state) && $state !== '' ? strtolower(trim($state)) : null;
        } catch (\Throwable $e) {
            $this->warn("  Paiement #{$payment->id} : passerelle injoignable ({$e->getMessage()})");

            return null;
        }
    }

    private function renderReport(int $confirmed, array $unpaid, array $unverifiable): void
    {
        $this->line("Paiements confirmés par la passerelle : {$confirmed}");

        if (! empty($unverifiable)) {
            $this->warn(count($unverifiable) . ' commande(s) invérifiable(s) (facture inconnue ou passerelle muette) — laissées telles quelles :');
            foreach ($unverifiable as $payment) {
                $this->line('  ' . $payment->order->reference . '  ' . $this->money($payment->order->total_amount));
            }
        }

        if (empty($unpaid)) {
            $this->newLine();
            $this->info('Aucune commande créditée à tort.');

            return;
        }

        $this->newLine();
        $this->error(count($unpaid) . ' commande(s) créditée(s) à tort :');
        $this->table(
            ['Commande', 'Montant', 'État e-billing', 'Billets', 'Scannés'],
            collect($unpaid)->map(function ($row) {
                $order = $row['payment']->order;

                return [
                    $order->reference,
                    $this->money($order->total_amount),
                    $row['state'],
                    $order->tickets->count(),
                    $order->tickets->where('status', 'used')->count(),
                ];
            })->all()
        );

        $scanned = collect($unpaid)->sum(fn ($row) => $row['payment']->order->tickets->where('status', 'used')->count());
        if ($scanned > 0) {
            $this->warn("⚠ {$scanned} billet(s) non payé(s) ont déjà été scannés à l'entrée : ces personnes sont entrées, à traiter à part.");
        }
    }

    /**
     * Annule la commande et relâche ses places. Retourne le nombre de billets libérés.
     */
    private function cancelOrder(Payment $payment, string $state): int
    {
        $order = $payment->order;

        return DB::transaction(function () use ($order, $payment, $state) {
            $payment->update(['status' => 'failed', 'paid_at' => null]);
            $order->update(['status' => 'cancelled']);

            // L'enum tickets.status n'a pas 'cancelled' : 'void' relâche la place
            // (même convention que WebhookController).
            $released = 0;
            foreach ($order->tickets as $ticket) {
                if ($ticket->status === 'used') {
                    // Déjà entré : on ne réécrit pas l'histoire du contrôle d'accès.
                    continue;
                }

                $ticket->update(['status' => 'void', 'issued_at' => null]);
                $released++;
            }

            Log::warning('Réconciliation e-billing : commande annulée faute de paiement', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'ebilling_state' => $state,
                'tickets_released' => $released,
            ]);

            $this->line("  {$order->reference} annulée ({$released} place(s) relâchée(s))");

            return $released;
        });
    }

    private function money($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }
}
