<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
        $order = $this->order;
        $ticketsCount = $order->tickets->count();
        $eventTitle = $order->tickets->first()?->event->title ?? 'Événement';

        return (new MailMessage)
            ->subject('Confirmation de commande - Primea Ticketing')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Client') . ' !')
            ->line('Votre commande a été créée avec succès.')
            ->line('**Référence de commande** : ' . $order->reference)
            ->line('**Événement** : ' . $eventTitle)
            ->line('**Nombre de billets** : ' . $ticketsCount)
            ->line('**Montant total** : ' . number_format($order->total_amount, 0, ',', ' ') . ' XAF')
            ->line('**Statut** : ' . ($order->status === 'paid' ? 'Payée' : 'En attente de paiement'))
            ->action('Voir ma commande', url('/account/orders/' . $order->reference))
            ->line('Merci d\'avoir choisi Primea Ticketing !');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_reference' => $this->order->reference,
            'total_amount' => $this->order->total_amount,
            'status' => $this->order->status,
            'message' => 'Votre commande ' . $this->order->reference . ' a été créée.',
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
