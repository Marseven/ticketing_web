<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
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
        $ticket = $this->ticket;
        $event = $ticket->event;
        $schedule = $ticket->schedule;

        $startsAt = $schedule ? $schedule->starts_at->format('d/m/Y à H:i') : 'Date à confirmer';
        $venue = $event->venue;
        $venueName = $venue ? $venue->name : 'Lieu à confirmer';
        $venueAddress = $venue ? $venue->address : '';

        return (new MailMessage)
            ->subject('Rappel : Votre événement approche - ' . $event->title)
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Client') . ' !')
            ->line('C\'est bientôt ! Votre événement **' . $event->title . '** aura lieu demain.')
            ->line('**📅 Date et heure** : ' . $startsAt)
            ->line('**📍 Lieu** : ' . $venueName)
            ->line($venueAddress ? '**Adresse** : ' . $venueAddress : '')
            ->line('**🎫 Référence du billet** : ' . $ticket->code)
            ->action('Voir mon billet', url('/account/orders/' . $ticket->order->reference))
            ->line('**Conseils pratiques** :')
            ->line('- Arrivez 30 minutes avant le début')
            ->line('- Préparez votre billet (version imprimée ou numérique)')
            ->line('- Vérifiez que votre QR code est lisible')
            ->line('Nous vous souhaitons un excellent moment !');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $event = $this->ticket->event;
        $schedule = $this->ticket->schedule;

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->code,
            'event_id' => $event->id,
            'event_title' => $event->title,
            'starts_at' => $schedule?->starts_at,
            'message' => 'Rappel : ' . $event->title . ' a lieu demain !',
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
