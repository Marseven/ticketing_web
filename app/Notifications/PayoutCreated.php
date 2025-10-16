<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutCreated extends Notification
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

        return (new MailMessage)
            ->subject('Demande de retrait créée - Primea Ticketing')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Organisateur') . ' !')
            ->line('Votre demande de retrait a été créée avec succès.')
            ->line('**Référence** : ' . $payout->reference)
            ->line('**Montant** : ' . number_format($payout->amount, 0, ',', ' ') . ' XAF')
            ->line('**Mode de paiement** : ' . $payout->gateway_display_name)
            ->line('**Numéro** : ' . $payout->payee_msisdn)
            ->line('**Type** : ' . $payout->payout_type_display_name)
            ->line('**Statut** : ' . $payout->status_display_name)
            ->line('')
            ->line('Votre demande est en cours de traitement. Vous recevrez une notification dès que le paiement sera effectué.')
            ->action('Voir mes retraits', url('/organizer/payouts'))
            ->line('⏱️ **Délai de traitement** : Les retraits sont généralement traités sous 24-48 heures.')
            ->line('Si vous avez des questions, n\'hésitez pas à nous contacter.');
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
            'message' => 'Demande de retrait de ' . number_format($this->payout->amount, 0, ',', ' ') . ' XAF créée.',
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
