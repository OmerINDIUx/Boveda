<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('password.set', [
            'token' => $this->token,
            'email' => $this->email,
        ]);

        return (new MailMessage)
            ->subject('Bienvenido a Bóveda - Configura tu contraseña')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Se te ha registrado como nuevo usuario en el sistema Bóveda.')
            ->line('Para poder acceder, necesitas configurar tu contraseña.')
            ->action('Configurar Contraseña', $url)
            ->line('Si no esperabas esta invitación, puedes ignorar este correo.')
            ->salutation('Saludos, El equipo de Bóveda');
    }
}
