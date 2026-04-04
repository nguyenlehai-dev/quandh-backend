<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingAgendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'title' => $this->title,
            'description' => $this->description,
            'order_index' => $this->order_index,
            'duration' => $this->duration,
            'presenter_id' => $this->presenter_id,
            'presenter_name' => $this->whenLoaded('presenter', fn () => $this->presenter?->name),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
