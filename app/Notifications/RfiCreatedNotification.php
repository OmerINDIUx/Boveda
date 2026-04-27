<?php

namespace App\Notifications;

use App\Models\Rfi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RfiCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rfi;

    public function __construct(Rfi $rfi)
    {
        $this->rfi = $rfi;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("Nuevo RFI Asignado: {$this->rfi->number}")
                    ->greeting("Hola {$notifiable->name},")
                    ->line("Se ha creado un nuevo RFI en el proyecto: {$this->rfi->project->name}")
                    ->line("Número: {$this->rfi->number}")
                    ->line("Asunto: {$this->rfi->subject}")
                    ->line("Prioridad: {$this->rfi->priority}")
                    ->action('Ver RFI', url("/rfis/{$this->rfi->id}"))
                    ->line('Por favor, revise la información y proporcione una respuesta técnica.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'rfi_id' => $this->rfi->id,
            'number' => $this->rfi->number,
            'subject' => $this->rfi->subject,
            'message' => "Se te ha asignado un nuevo RFI: {$this->rfi->number}",
            'url' => "/rfis/{$this->rfi->id}"
        ];
    }
}
