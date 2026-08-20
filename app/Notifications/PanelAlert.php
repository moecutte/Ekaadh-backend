<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PanelAlert extends Notification
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $title,
        public string $body,
        public string $kind,
        public ?string $url = null,
        public array $meta = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'kind' => $this->kind,
            'url' => $this->url,
            'meta' => $this->meta,
        ];
    }
}
