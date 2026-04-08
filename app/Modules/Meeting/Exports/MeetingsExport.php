<?php

namespace App\Modules\Meeting\Exports;

use App\Modules\Meeting\Models\Meeting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MeetingsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection()
    {
        return Meeting::query()
            ->with(['meetingType', 'creator', 'editor'])
            ->withCount(['participants', 'agendas', 'documents', 'conclusions', 'votings'])
            ->filter($this->filters)
            ->get()
            ->map(fn (Meeting $meeting) => [
                'id' => $meeting->id,
                'code' => $meeting->code,
                'title' => $meeting->title,
                'meeting_type' => $meeting->meetingType?->name,
                'meeting_type_id' => $meeting->meeting_type_id,
                'description' => $meeting->description,
                'location' => $meeting->location,
                'start_at' => $meeting->start_at?->format('Y-m-d H:i:s'),
                'end_at' => $meeting->end_at?->format('Y-m-d H:i:s'),
                'status' => $meeting->status,
                'participants_count' => $meeting->participants_count,
                'agendas_count' => $meeting->agendas_count,
                'documents_count' => $meeting->documents_count,
                'conclusions_count' => $meeting->conclusions_count,
                'votings_count' => $meeting->votings_count,
                'qr_token' => $meeting->qr_token,
                'created_by' => $meeting->creator?->name ?? 'N/A',
                'updated_by' => $meeting->editor?->name ?? 'N/A',
                'created_at' => $meeting->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $meeting->updated_at?->format('Y-m-d H:i:s'),
            ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Title',
            'Meeting Type',
            'Meeting Type ID',
            'Description',
            'Location',
            'Start At',
            'End At',
            'Status',
            'Participants Count',
            'Agendas Count',
            'Documents Count',
            'Conclusions Count',
            'Votings Count',
            'QR Token',
            'Created By',
            'Updated By',
            'Created At',
            'Updated At',
        ];
    }
}
