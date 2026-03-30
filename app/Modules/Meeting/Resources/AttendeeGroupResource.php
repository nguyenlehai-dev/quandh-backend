<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendeeGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'meeting_type_id' => $this->meeting_type_id,
            'meeting_type_name' => $this->whenLoaded('meetingType', fn () => $this->meetingType->name),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'position' => $u->pivot->position,
            ])),
            'members_count' => $this->whenLoaded('members', fn () => $this->members->count()),
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
