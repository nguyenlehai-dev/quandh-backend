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
            'meeting_type_id' => $this->meeting_type_id,
            'meeting_type' => $this->whenLoaded('meetingType', function () {
                return [
                    'id' => $this->meetingType->id,
                    'name' => $this->meetingType->name,
                ];
            }),
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_at' => $this->start_at?->format('H:i:s d/m/Y'),
            'end_at' => $this->end_at?->format('H:i:s d/m/Y'),
            'status' => $this->status,
            'qr_token' => $this->when($this->qr_token, $this->qr_token),

            // Counts (withCount)
            'participants_count' => $this->whenCounted('participants'),
            'agendas_count' => $this->whenCounted('agendas'),
            'documents_count' => $this->whenCounted('documents'),
            'conclusions_count' => $this->whenCounted('conclusions'),

            // Relationships (whenLoaded)
            'participants' => MeetingParticipantResource::collection($this->whenLoaded('participants')),
            'agendas' => MeetingAgendaResource::collection($this->whenLoaded('agendas')),
            'documents' => MeetingDocumentResource::collection($this->whenLoaded('documents')),
            'conclusions' => MeetingConclusionResource::collection($this->whenLoaded('conclusions')),
            'votings' => MeetingVotingResource::collection($this->whenLoaded('votings')),

            'created_by' => $this->creator->name ?? 'N/A',
            'updated_by' => $this->editor->name ?? 'N/A',
            'created_at' => $this->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $this->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
