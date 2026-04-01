<?php

namespace App\Modules\Meeting\Events;

use App\Modules\Meeting\Models\MeetingParticipant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện điểm danh thành viên (bao gồm QR checkin).
 * Broadcast real-time cho admin thấy cập nhật bảng điểm danh.
 */
class MeetingAttendanceChecked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MeetingParticipant $participant,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('meeting.'.$this->participant->meeting_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participant->id,
            'user_id' => $this->participant->user_id,
            'user_name' => $this->participant->user?->name ?? 'N/A',
            'attendance_status' => $this->participant->attendance_status,
            'checkin_at' => $this->participant->checkin_at?->format('H:i:s d/m/Y'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'meeting.attendance.checked';
    }
}
