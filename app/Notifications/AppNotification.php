<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public ?string $key = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];

        if ($this->key !== null) {
            $data['key'] = $this->key;
        }

        return $data;
    }
}
