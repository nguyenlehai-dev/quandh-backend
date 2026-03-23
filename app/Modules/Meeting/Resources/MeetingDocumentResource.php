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
            'title' => $this->title,
            'description' => $this->description,
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
            'created_by' => $this->creator->name ?? 'N/A',
            'updated_by' => $this->editor->name ?? 'N/A',
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
