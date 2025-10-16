<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payout;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payout $payout)
    {
        $this->payout = $payout;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $payout = $this->payout;
        $failureReason = $payout->failure_reason ?? 'Raison non spécifiée';
        $processedDate = $payout->processed_at ? $payout->processed_at->format('d/m/Y à H:i') : 'Date inconnue';

        return (new MailMessage)
            ->subject('Échec du retrait - Action requise')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Organisateur') . ',')
            ->line('Nous vous informons que votre demande de retrait n\'a pas pu être traitée.')
            ->line('**Référence** : ' . $payout->reference)
            ->line('**Montant** : ' . number_format($payout->amount, 0, ',', ' ') . ' XAF')
            ->line('**Mode de paiement** : ' . $payout->gateway_display_name)
            ->line('**Numéro** : ' . $payout->payee_msisdn)
            ->line('**Date de traitement** : ' . $processedDate)
            ->line('**Raison de l\'échec** : ' . $failureReason)
            ->line('')
            ->action('Réessayer le retrait', url('/organizer/payouts'))
            ->line('**Solutions possibles** :')
            ->line('- Vérifiez que le numéro de téléphone est correct et actif')
            ->line('- Assurez-vous que le compte mobile peut recevoir des fonds')
            ->line('- Vérifiez que vous avez suffisamment de solde disponible')
            ->line('- Si le problème persiste, contactez notre support avec la référence ci-dessus');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'payout_id' => $this->payout->id,
            'payout_reference' => $this->payout->reference,
            'amount' => $this->payout->amount,
            'gateway' => $this->payout->gateway,
            'status' => $this->payout->status,
            'failure_reason' => $this->payout->failure_reason,
            'processed_at' => $this->payout->processed_at,
            'message' => 'Échec du retrait de ' . number_format($this->payout->amount, 0, ',', ' ') . ' XAF.',
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
