<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_type_id' => $this->when(isset($this->meeting_type_id), $this->meeting_type_id),
            'name' => $this->name,
            'position' => $this->when(isset($this->position), $this->position),
            'description' => $this->description,
            'status' => $this->status,
            'meeting_type' => $this->whenLoaded('meetingType', fn () => $this->meetingType ? [
                'id' => $this->meetingType->id,
                'name' => $this->meetingType->name,
            ] : null),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'position' => $member->position,
                'user' => $member->relationLoaded('user') && $member->user ? [
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                ] : null,
            ])->values()),
            'created_by' => $this->creator?->name ?? 'N/A',
            'updated_by' => $this->editor?->name ?? 'N/A',
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
