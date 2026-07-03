<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventApproved extends Notification
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
            ->subject('Votre événement a été approuvé - MyTicketO')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Organisateur') . ' !')
            ->line('Bonne nouvelle : votre événement **' . $event->title . '** a été approuvé.')
            ->line('**Commission appliquée** : ' . number_format($event->effectiveCommission(), 2, ',', ' ') . ' %')
            ->line('Vous pouvez désormais mettre vos billets en vente.')
            ->action('Voir mon événement', url('/organizer/events'))
            ->line('Merci d\'utiliser MyTicketO !');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'commission_percentage' => $this->event->effectiveCommission(),
            'message' => 'Votre événement "' . $this->event->title . '" a été approuvé.',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
