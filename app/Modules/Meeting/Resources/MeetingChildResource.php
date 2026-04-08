<?php

namespace App\Modules\Meeting\Resources;

use App\Modules\Meeting\Models\MeetingAgenda;
use App\Modules\Meeting\Models\MeetingConclusion;
use App\Modules\Meeting\Models\MeetingDocument;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Models\MeetingPersonalNote;
use App\Modules\Meeting\Models\MeetingReminder;
use App\Modules\Meeting\Models\MeetingSpeechRequest;
use App\Modules\Meeting\Models\MeetingVoteResult;
use App\Modules\Meeting\Models\MeetingVoting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingChildResource extends JsonResource
{
    protected function audit(object $item): array
    {
        return [
            'created_by' => $item->creator?->name ?? 'N/A',
            'updated_by' => $item->editor?->name ?? 'N/A',
            'created_at' => $item->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $item->updated_at?->format('H:i:s d/m/Y'),
        ];
    }

    public function toArray(Request $request): array
    {
        return match (true) {
            $this->resource instanceof MeetingParticipant => $this->participant($this->resource),
            $this->resource instanceof MeetingAgenda => $this->agenda($this->resource),
            $this->resource instanceof MeetingDocument => $this->document($this->resource),
            $this->resource instanceof MeetingConclusion => $this->conclusion($this->resource),
            $this->resource instanceof MeetingSpeechRequest => $this->speechRequest($this->resource),
            $this->resource instanceof MeetingVoting => $this->voting($this->resource),
            $this->resource instanceof MeetingVoteResult => $this->voteResult($this->resource),
            $this->resource instanceof MeetingPersonalNote => $this->personalNote($this->resource),
            $this->resource instanceof MeetingReminder => $this->reminder($this->resource),
            default => $this->resource->toArray(),
        };
    }

    protected function user($user): ?array
    {
        return $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ] : null;
    }

    protected function meeting($meeting): ?array
    {
        return $meeting ? [
            'id' => $meeting->id,
            'code' => $meeting->code,
            'title' => $meeting->title,
            'status' => $meeting->status,
            'start_at' => $meeting->start_at?->toISOString(),
            'end_at' => $meeting->end_at?->toISOString(),
            'meeting_type' => $meeting->relationLoaded('meetingType') && $meeting->meetingType ? [
                'id' => $meeting->meetingType->id,
                'name' => $meeting->meetingType->name,
            ] : null,
        ] : null;
    }

    protected function participant(MeetingParticipant $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'user_id' => $item->user_id,
            'role' => $item->role,
            'position' => $item->position,
            'status' => $item->status,
            'checkin_at' => $item->checkin_at?->toISOString(),
            'absence_reason' => $item->absence_reason,
            'delegated_to_id' => $item->delegated_to_id,
            'sort_order' => $item->sort_order,
            'meeting' => $item->relationLoaded('meeting') ? $this->meeting($item->meeting) : null,
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
            'delegated_to' => $item->relationLoaded('delegatedTo') ? $this->user($item->delegatedTo) : null,
        ], $this->audit($item));
    }

    protected function agenda(MeetingAgenda $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'title' => $item->title,
            'description' => $item->description,
            'sort_order' => $item->sort_order,
            'duration_minutes' => $item->duration_minutes,
            'presenter_id' => $item->presenter_id,
            'status' => $item->status,
            'presenter' => $item->relationLoaded('presenter') ? $this->user($item->presenter) : null,
        ], $this->audit($item));
    }

    protected function document(MeetingDocument $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'agenda_id' => $item->agenda_id,
            'title' => $item->title,
            'description' => $item->description,
            'document_number' => $item->document_number,
            'issued_at' => $item->issued_at?->format('Y-m-d'),
            'status' => $item->status,
            'attachments' => $item->relationLoaded('media') ? $item->media->where('collection_name', 'meeting-document-attachments')->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'url' => $media->getUrl(),
            ])->values() : [],
        ], $this->audit($item));
    }

    protected function conclusion(MeetingConclusion $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'agenda_id' => $item->agenda_id,
            'title' => $item->title,
            'content' => $item->content,
        ], $this->audit($item));
    }

    protected function speechRequest(MeetingSpeechRequest $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'agenda_id' => $item->agenda_id,
            'user_id' => $item->user_id,
            'content' => $item->content,
            'status' => $item->status,
            'review_note' => $item->review_note,
            'reviewed_by' => $item->reviewed_by,
            'reviewed_at' => $item->reviewed_at?->toISOString(),
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
        ], $this->audit($item));
    }

    protected function voting(MeetingVoting $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'agenda_id' => $item->agenda_id,
            'title' => $item->title,
            'description' => $item->description,
            'type' => $item->type,
            'status' => $item->status,
            'options' => $item->options,
            'opened_at' => $item->opened_at?->toISOString(),
            'closed_at' => $item->closed_at?->toISOString(),
            'results' => $item->relationLoaded('results') ? $item->results->map(fn ($result) => $this->voteResult($result))->values() : [],
        ], $this->audit($item));
    }

    protected function voteResult(MeetingVoteResult $item): array
    {
        return array_merge([
            'id' => $item->id,
            'voting_id' => $item->voting_id,
            'user_id' => $item->user_id,
            'option' => $item->option,
            'note' => $item->note,
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
        ], $this->audit($item));
    }

    protected function personalNote(MeetingPersonalNote $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'document_id' => $item->document_id,
            'user_id' => $item->user_id,
            'content' => $item->content,
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
        ], $this->audit($item));
    }

    protected function reminder(MeetingReminder $item): array
    {
        return array_merge([
            'id' => $item->id,
            'meeting_id' => $item->meeting_id,
            'user_id' => $item->user_id,
            'title' => $item->title,
            'content' => $item->content,
            'remind_at' => $item->remind_at?->toISOString(),
            'status' => $item->status,
            'user' => $item->relationLoaded('user') ? $this->user($item->user) : null,
        ], $this->audit($item));
    }
}
