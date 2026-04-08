<?php

namespace App\Modules\Meeting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'meeting_type_id' => $this->meeting_type_id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),
            'status' => $this->status,
            'qr_token' => $this->qr_token,
            'active_agenda_id' => $this->active_agenda_id,
            'meeting_type' => $this->whenLoaded('meetingType', fn () => $this->meetingType ? [
                'id' => $this->meetingType->id,
                'name' => $this->meetingType->name,
            ] : null),
            'active_agenda' => $this->whenLoaded('activeAgenda', fn () => $this->activeAgenda ? $this->agenda($this->activeAgenda) : null),
            'participants_count' => $this->whenCounted('participants'),
            'agendas_count' => $this->whenCounted('agendas'),
            'documents_count' => $this->whenCounted('documents'),
            'conclusions_count' => $this->whenCounted('conclusions'),
            'votings_count' => $this->whenCounted('votings'),
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($item) => $this->participant($item))->values()),
            'agendas' => $this->whenLoaded('agendas', fn () => $this->agendas->map(fn ($item) => $this->agenda($item))->values()),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($item) => $this->document($item))->values()),
            'conclusions' => $this->whenLoaded('conclusions', fn () => $this->conclusions->map(fn ($item) => $this->conclusion($item))->values()),
            'speech_requests' => $this->whenLoaded('speechRequests', fn () => $this->speechRequests->map(fn ($item) => $this->speechRequest($item))->values()),
            'votings' => $this->whenLoaded('votings', fn () => $this->votings->map(fn ($item) => $this->voting($item))->values()),
            'personal_notes' => $this->whenLoaded('personalNotes', fn () => $this->personalNotes->map(fn ($item) => $this->personalNote($item))->values()),
            'reminders' => $this->whenLoaded('reminders', fn () => $this->reminders->map(fn ($item) => $this->reminder($item))->values()),
            'created_by' => $this->creator?->name ?? 'N/A',
            'updated_by' => $this->editor?->name ?? 'N/A',
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }

    protected function user($user): ?array
    {
        return $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ] : null;
    }

    protected function participant($item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'role' => $item->role,
            'position' => $item->position,
            'status' => $item->status,
            'checkin_at' => $item->checkin_at?->toISOString(),
            'absence_reason' => $item->absence_reason,
            'delegated_to_id' => $item->delegated_to_id,
            'sort_order' => $item->sort_order,
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
            'delegated_to' => $item->relationLoaded('delegatedTo') ? $this->user($item->delegatedTo) : null,
        ];
    }

    protected function agenda($item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'sort_order' => $item->sort_order,
            'duration_minutes' => $item->duration_minutes,
            'presenter_id' => $item->presenter_id,
            'status' => $item->status,
            'presenter' => $item->relationLoaded('presenter') ? $this->user($item->presenter) : null,
        ];
    }

    protected function document($item): array
    {
        return [
            'id' => $item->id,
            'agenda_id' => $item->agenda_id,
            'document_type_id' => $item->document_type_id,
            'document_field_id' => $item->document_field_id,
            'issuing_agency_id' => $item->issuing_agency_id,
            'document_signer_id' => $item->document_signer_id,
            'title' => $item->title,
            'description' => $item->description,
            'document_number' => $item->document_number,
            'issued_at' => $item->issued_at?->format('Y-m-d'),
            'status' => $item->status,
            'agenda' => $item->relationLoaded('agenda') && $item->agenda ? ['id' => $item->agenda->id, 'title' => $item->agenda->title] : null,
            'document_type' => $item->relationLoaded('documentType') && $item->documentType ? ['id' => $item->documentType->id, 'name' => $item->documentType->name] : null,
            'document_field' => $item->relationLoaded('documentField') && $item->documentField ? ['id' => $item->documentField->id, 'name' => $item->documentField->name] : null,
            'issuing_agency' => $item->relationLoaded('issuingAgency') && $item->issuingAgency ? ['id' => $item->issuingAgency->id, 'name' => $item->issuingAgency->name] : null,
            'document_signer' => $item->relationLoaded('documentSigner') && $item->documentSigner ? ['id' => $item->documentSigner->id, 'name' => $item->documentSigner->name] : null,
            'attachments' => $item->relationLoaded('media') ? $item->media->where('collection_name', 'meeting-document-attachments')->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'url' => $media->getUrl(),
            ])->values() : [],
        ];
    }

    protected function conclusion($item): array
    {
        return [
            'id' => $item->id,
            'agenda_id' => $item->agenda_id,
            'title' => $item->title,
            'content' => $item->content,
            'agenda' => $item->relationLoaded('agenda') && $item->agenda ? ['id' => $item->agenda->id, 'title' => $item->agenda->title] : null,
        ];
    }

    protected function speechRequest($item): array
    {
        return [
            'id' => $item->id,
            'agenda_id' => $item->agenda_id,
            'user_id' => $item->user_id,
            'content' => $item->content,
            'status' => $item->status,
            'review_note' => $item->review_note,
            'reviewed_by' => $item->reviewed_by,
            'reviewed_at' => $item->reviewed_at?->toISOString(),
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
            'agenda' => $item->relationLoaded('agenda') && $item->agenda ? ['id' => $item->agenda->id, 'title' => $item->agenda->title] : null,
        ];
    }

    protected function voting($item): array
    {
        return [
            'id' => $item->id,
            'agenda_id' => $item->agenda_id,
            'title' => $item->title,
            'description' => $item->description,
            'type' => $item->type,
            'status' => $item->status,
            'options' => $item->options,
            'opened_at' => $item->opened_at?->toISOString(),
            'closed_at' => $item->closed_at?->toISOString(),
            'agenda' => $item->relationLoaded('agenda') && $item->agenda ? ['id' => $item->agenda->id, 'title' => $item->agenda->title] : null,
            'results' => $item->relationLoaded('results') ? $item->results->map(fn ($result) => [
                'id' => $result->id,
                'user_id' => $result->user_id,
                'option' => $result->option,
                'note' => $result->note,
                'user' => $result->relationLoaded('user') ? $this->user($result->user) : null,
            ])->values() : [],
        ];
    }

    protected function personalNote($item): array
    {
        return [
            'id' => $item->id,
            'document_id' => $item->document_id,
            'user_id' => $item->user_id,
            'content' => $item->content,
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
            'document' => $item->relationLoaded('document') && $item->document ? ['id' => $item->document->id, 'title' => $item->document->title] : null,
        ];
    }

    protected function reminder($item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'title' => $item->title,
            'content' => $item->content,
            'remind_at' => $item->remind_at?->toISOString(),
            'status' => $item->status,
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
        ];
    }
}
