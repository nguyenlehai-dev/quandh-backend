<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'meeting_title' => $this->whenLoaded('meeting', fn () => $this->meeting->title),
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'user_email' => $this->whenLoaded('user', fn () => $this->user->email),
            'position' => $this->position,
            'meeting_role' => $this->meeting_role,
            'attendance_status' => $this->attendance_status,
            'checkin_at' => $this->checkin_at?->format('H:i:s d/m/Y'),
            'absence_reason' => $this->absence_reason,
            'delegated_to_id' => $this->delegated_to_id,
            'delegated_to_name' => $this->whenLoaded('delegatedTo', fn () => $this->delegatedTo?->name),
            'is_guest' => (bool) $this->is_guest,
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
