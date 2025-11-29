<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewEventNotification extends Notification
{
    use Queueable;

    public $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    // Specifica entrambi i canali
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // 1. Cosa salvare nel DB (per quando l'utente è offline)
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->payload['message'] ?? null,
            'created_at' => now(),
        ];
    }

    // 2. Cosa inviare a Reverb (per quando l'utente è online)
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'data' => [
                'message' => $this->payload['message'] ?? null,
            ]
        ]);
    }
}