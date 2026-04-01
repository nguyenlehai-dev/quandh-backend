<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingConclusionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'meeting_title' => $this->whenLoaded('meeting', fn () => $this->meeting->title),
            'meeting_agenda_id' => $this->meeting_agenda_id,
            'title' => $this->title,
            'content' => $this->content,
            'agenda_title' => $this->whenLoaded('agenda', fn () => $this->agenda->title),
            'created_by' => $this->creator->name ?? 'N/A',
            'updated_by' => $this->editor->name ?? 'N/A',
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
