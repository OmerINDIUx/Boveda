<?php

namespace App\Notifications;

use App\Models\Rfi;
use App\Models\RfiResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RfiResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rfi;
    protected $response;

    public function __construct(Rfi $rfi, RfiResponse $response)
    {
        $this->rfi = $rfi;
        $this->response = $response;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("Respuesta a RFI: {$this->rfi->number}")
                    ->greeting("Hola {$notifiable->name},")
                    ->line("Hay una nueva respuesta en el RFI {$this->rfi->number}:")
                    ->line("De: {$this->response->user->name}")
                    ->line("Mensaje: " . substr($this->response->message, 0, 100) . "...")
                    ->action('Ver Conversación', url("/rfis/{$this->rfi->id}"))
                    ->line('Manténgase al tanto de las actualizaciones del proyecto.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'rfi_id' => $this->rfi->id,
            'number' => $this->rfi->number,
            'subject' => $this->rfi->subject,
            'message' => "Nueva respuesta en {$this->rfi->number} por {$this->response->user->name}",
            'url' => "/rfis/{$this->rfi->id}"
        ];
    }
}
