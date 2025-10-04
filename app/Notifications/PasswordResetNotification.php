<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification // implements ShouldQueue - Désactivé pour envoi immédiat
{
    // use Queueable; - Désactivé pour envoi immédiat

    protected $token;
    protected $isNewUser;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $isNewUser = false)
    {
        $this->token = $token;
        $this->isNewUser = $isNewUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/reset-password?token=' . $this->token . '&email=' . $notifiable->email);

        if ($this->isNewUser) {
            return (new MailMessage)
                ->subject('Bienvenue sur ' . config('app.name') . ' - Définissez votre mot de passe')
                ->greeting('Bonjour ' . $notifiable->name . ',')
                ->line('Un compte a été créé pour vous sur ' . config('app.name') . '.')
                ->line('Pour activer votre compte, veuillez définir votre mot de passe en cliquant sur le bouton ci-dessous.')
                ->action('Définir mon mot de passe', $url)
                ->line('Ce lien expirera dans 60 minutes.')
                ->line('Si vous n\'avez pas demandé la création de ce compte, ignorez simplement cet email.');
        }

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Ce lien expirera dans 60 minutes.')
            ->line('Si vous n\'avez pas demandé cette réinitialisation, aucune action n\'est requise.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
