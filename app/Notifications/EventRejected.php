<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRejected extends Notification
{
    use Queueable;

    protected $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->event;

        return (new MailMessage)
            ->subject('Votre événement nécessite des modifications - MyTicketO')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Organisateur') . ' !')
            ->line('Votre événement **' . $event->title . '** n\'a pas été approuvé en l\'état.')
            ->line('**Motif** : ' . ($event->rejection_reason ?: 'Non précisé'))
            ->line('Merci de corriger les points indiqués puis de resoumettre votre événement.')
            ->action('Modifier mon événement', url('/organizer/events'))
            ->line('Notre équipe reste à votre disposition.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'rejection_reason' => $this->event->rejection_reason,
            'message' => 'Votre événement "' . $this->event->title . '" a été rejeté.',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
