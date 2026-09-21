<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        // Ne dispatcher le job qu'après le commit : sinon le worker peut
        // lire la commande avant qu'elle soit visible en base.
        // (Méthode du trait Queueable : redéclarer la propriété
        // `$afterCommit` ici serait une erreur fatale en PHP 8.3.)
        $this->afterCommit();

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

        // Ce courriel part à la création de la commande, donc AVANT le
        // paiement : à ce moment il n'existe encore aucun billet. La quantité
        // et l'événement se lisent sur ce qui a été commandé.
        $ticketsCount = $order->tickets->count() ?: (int) $order->items->sum('qty');
        $eventTitle = $order->resolveEvent()?->title ?? 'Événement';

        return (new MailMessage)
            ->subject('Confirmation de commande - Primea')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Client') . ' !')
            ->line($order->status === 'paid'
                ? 'Votre commande est confirmée, vos billets sont disponibles.'
                : 'Votre commande a bien été enregistrée. Vos billets seront émis dès la confirmation du paiement.')
            ->line('**Référence de commande** : ' . $order->reference)
            ->line('**Événement** : ' . $eventTitle)
            ->line('**Nombre de billets** : ' . $ticketsCount)
            ->line('**Montant total** : ' . number_format($order->total_amount, 0, ',', ' ') . ' XAF')
            ->line('**Statut** : ' . ($order->status === 'paid' ? 'Payée' : 'En attente de paiement'))
            ->action('Voir ma commande', url('/account/orders/' . $order->reference))
            ->line('Merci d\'avoir choisi Primea !');
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
