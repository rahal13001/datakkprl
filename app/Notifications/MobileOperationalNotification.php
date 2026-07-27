<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MobileOperationalNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly array $payload,
        string $id,
    ) {
        $this->id = $id;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
