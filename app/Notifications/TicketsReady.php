<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketsReady extends Notification
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
        $tickets = $order->tickets;
        $event = $tickets->first()?->event;

        return (new MailMessage)
            ->subject('Vos billets sont disponibles - MyTicketO')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Client') . ' !')
            ->line('Vos billets pour **' . ($event?->title ?? 'l\'événement') . '** sont maintenant disponibles !')
            ->line('**Commande** : ' . $order->reference)
            ->line('**Nombre de billets** : ' . $tickets->count())
            ->action('Télécharger mes billets', url('/account/orders/' . $order->reference))
            ->line('💡 **Conseils** :')
            ->line('- Téléchargez vos billets avant le jour de l\'événement')
            ->line('- Vous pouvez les imprimer ou les présenter depuis votre téléphone')
            ->line('- Chaque billet contient un QR code unique')
            ->line('À bientôt !');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $event = $this->order->tickets->first()?->event;

        return [
            'order_id' => $this->order->id,
            'order_reference' => $this->order->reference,
            'tickets_count' => $this->order->tickets->count(),
            'event_title' => $event?->title,
            'message' => 'Vos billets pour ' . ($event?->title ?? 'l\'événement') . ' sont disponibles.',
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
