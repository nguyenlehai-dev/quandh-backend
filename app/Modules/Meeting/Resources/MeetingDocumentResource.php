<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MeetingDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'meeting_agenda_id' => $this->meeting_agenda_id,
            'document_type_id' => $this->document_type_id,
            'document_field_id' => $this->document_field_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'files' => $this->whenLoaded('media', function () {
                return $this->media
                    ->where('collection_name', 'meeting-document-files')
                    ->sortBy('order_column')
                    ->values()
                    ->map(fn (Media $media) => [
                        'id' => $media->id,
                        'url' => $media->getFullUrl(),
                        'original_name' => $media->getCustomProperty('original_name') ?: $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                    ]);
            }),
            'agenda_title' => $this->whenLoaded('agenda', fn () => $this->agenda?->title),
            'created_by' => $this->creator->name ?? 'N/A',
            'updated_by' => $this->editor->name ?? 'N/A',
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
