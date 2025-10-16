<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessful extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
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
        $payment = $this->payment;
        $order = $payment->order;
        $ticketsCount = $order->tickets->count();

        return (new MailMessage)
            ->subject('Paiement réussi - Vos billets sont prêts !')
            ->greeting('Excellente nouvelle ' . ($notifiable->name ?? 'Client') . ' !')
            ->line('Votre paiement a été confirmé avec succès.')
            ->line('**Commande** : ' . $order->reference)
            ->line('**Montant payé** : ' . number_format($payment->amount, 0, ',', ' ') . ' XAF')
            ->line('**Méthode de paiement** : ' . strtoupper($payment->gateway))
            ->line('**Nombre de billets** : ' . $ticketsCount)
            ->action('Télécharger mes billets', url('/account/orders/' . $order->reference))
            ->line('Vos billets sont maintenant disponibles en téléchargement !')
            ->line('Conservez-les précieusement et présentez le QR code à l\'entrée de l\'événement.');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'order_id' => $this->payment->order_id,
            'order_reference' => $this->payment->order->reference,
            'amount' => $this->payment->amount,
            'gateway' => $this->payment->gateway,
            'message' => 'Votre paiement de ' . number_format($this->payment->amount, 0, ',', ' ') . ' XAF a été confirmé.',
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
