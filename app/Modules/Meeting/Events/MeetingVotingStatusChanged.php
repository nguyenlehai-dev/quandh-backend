<?php

namespace App\Modules\Meeting\Events;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingVoting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện thay đổi trạng thái biểu quyết (open/closed).
 * Broadcast đến đại biểu để hiển thị form bỏ phiếu hoặc kết quả.
 */
class MeetingVotingStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Meeting $meeting,
        public MeetingVoting $voting,
        public string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('meeting.'.$this->meeting->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'voting_id' => $this->voting->id,
            'voting_title' => $this->voting->title,
            'status' => $this->status,
            'type' => $this->voting->type,
        ];
    }

    public function broadcastAs(): string
    {
        return 'voting.status.changed';
    }
}
