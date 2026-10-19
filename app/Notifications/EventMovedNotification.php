<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventMovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $eventName,
        public ?string $oldRoom,
        public string $newRoom,
        public string $reason,
        public int $eventId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'event_moved',
            'event_id' => $this->eventId,
            'event_name' => $this->eventName,
            'old_room' => $this->oldRoom,
            'new_room' => $this->newRoom,
            'reason' => $this->reason,
            'message' => "Agenda {$this->eventName} dipindahkan ke {$this->newRoom}. Alasan: {$this->reason}",
        ];
    }
}
