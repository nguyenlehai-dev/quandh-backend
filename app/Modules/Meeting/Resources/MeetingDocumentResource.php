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
            'meeting_title' => $this->whenLoaded('meeting', fn () => $this->meeting->title),
            'title' => $this->title,
            'description' => $this->description,
            'document_type_id' => $this->document_type_id,
            'document_field_id' => $this->document_field_id,
            'issuing_agency_id' => $this->issuing_agency_id,
            'document_signer_id' => $this->document_signer_id,
            'document_type' => $this->whenLoaded('documentType', function () {
                return $this->documentType ? ['id' => $this->documentType->id, 'name' => $this->documentType->name] : null;
            }),
            'document_field' => $this->whenLoaded('documentField', function () {
                return $this->documentField ? ['id' => $this->documentField->id, 'name' => $this->documentField->name] : null;
            }),
            'issuing_agency' => $this->whenLoaded('issuingAgency', function () {
                return $this->issuingAgency ? ['id' => $this->issuingAgency->id, 'name' => $this->issuingAgency->name] : null;
            }),
            'document_signer' => $this->whenLoaded('documentSigner', function () {
                return $this->documentSigner ? ['id' => $this->documentSigner->id, 'name' => $this->documentSigner->name] : null;
            }),
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
