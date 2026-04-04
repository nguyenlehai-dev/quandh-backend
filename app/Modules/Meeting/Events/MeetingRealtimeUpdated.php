<?php

namespace App\Modules\Meeting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingRealtimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $meetingId,
        public string $eventType,
        public array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('meeting.'.$this->meetingId)];
    }

    public function broadcastAs(): string
    {
        return 'meeting.realtime.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'meeting_id' => $this->meetingId,
            'event_type' => $this->eventType,
            'payload' => $this->payload,
        ];
    }
}
