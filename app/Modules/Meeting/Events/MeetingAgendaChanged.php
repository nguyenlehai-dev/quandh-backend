<?php

namespace App\Modules\Meeting\Events;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingAgenda;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện chuyển mục Agenda trong cuộc họp.
 * Broadcast đến tất cả đại biểu để tự động chuyển trang/mục tương ứng.
 */
class MeetingAgendaChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Meeting $meeting,
        public MeetingAgenda $agenda,
    ) {}

    /** Broadcast trên kênh private của cuộc họp. */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('meeting.'.$this->meeting->id),
        ];
    }

    /** Dữ liệu gửi đến client. */
    public function broadcastWith(): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'agenda_id' => $this->agenda->id,
            'agenda_title' => $this->agenda->title,
            'order_index' => $this->agenda->order_index,
        ];
    }

    public function broadcastAs(): string
    {
        return 'agenda.changed';
    }
}
