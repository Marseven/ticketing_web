<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventApproved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Ne dispatcher le job qu'après le commit de la transaction : sinon le
     * worker peut lire la commande avant qu'elle soit visible en base.
     */
    public $afterCommit = true;

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
            ->subject('Votre événement a été approuvé - Primea')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Organisateur') . ' !')
            ->line('Bonne nouvelle : votre événement **' . $event->title . '** a été approuvé.')
            ->line('**Commission appliquée** : ' . number_format($event->effectiveCommission(), 2, ',', ' ') . ' %')
            ->line('Vous pouvez désormais mettre vos billets en vente.')
            ->action('Voir mon événement', url('/organizer/events'))
            ->line('Merci d\'utiliser Primea !');
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
