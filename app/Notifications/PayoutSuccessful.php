<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutSuccessful extends Notification implements ShouldQueue
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
        $processedDate = $payout->processed_at ? $payout->processed_at->format('d/m/Y à H:i') : 'Date inconnue';

        return (new MailMessage)
            ->subject('Retrait effectué avec succès - Primea Ticketing')
            ->greeting('Excellente nouvelle ' . ($notifiable->name ?? 'Organisateur') . ' !')
            ->line('Votre retrait a été effectué avec succès.')
            ->line('**Référence** : ' . $payout->reference)
            ->line('**Montant** : ' . number_format($payout->amount, 0, ',', ' ') . ' XAF')
            ->line('**Mode de paiement** : ' . $payout->gateway_display_name)
            ->line('**Numéro bénéficiaire** : ' . $payout->payee_msisdn)
            ->line('**Date de traitement** : ' . $processedDate)
            ->line('')
            ->line('Les fonds ont été transférés sur votre compte mobile.')
            ->action('Voir mes retraits', url('/organizer/payouts'))
            ->line('**Note** : Selon votre opérateur, le crédit peut prendre quelques minutes avant d\'apparaître sur votre compte.')
            ->line('Si vous ne recevez pas les fonds sous 30 minutes, veuillez nous contacter avec la référence ci-dessus.');
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
            'processed_at' => $this->payout->processed_at,
            'message' => 'Retrait de ' . number_format($this->payout->amount, 0, ',', ' ') . ' XAF effectué avec succès.',
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
