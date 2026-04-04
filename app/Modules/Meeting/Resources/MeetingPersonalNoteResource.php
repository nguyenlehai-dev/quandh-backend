<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingPersonalNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'meeting_document_id' => $this->meeting_document_id,
            'document_title' => $this->whenLoaded('document', fn () => $this->document->title),
            'content' => $this->content,
            'last_synced_at' => $this->last_synced_at?->format('H:i:s d/m/Y'),
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
