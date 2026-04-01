<?php

namespace App\Modules\Meeting\Exports;

use App\Modules\Meeting\Models\MeetingParticipant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeetingParticipantsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $limit = isset($this->filters['limit']) ? (int) $this->filters['limit'] : 1000;
        $page = isset($this->filters['page']) ? (int) $this->filters['page'] : 1;
        
        $query = MeetingParticipant::query()
            ->with(['meeting:id,title,meeting_type_id', 'meeting.meetingType', 'user'])
            ->has('meeting')
            ->orderBy('id', 'desc');

        if (!empty($this->filters['search'])) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->filters['search']}%");
            });
        }
        if (!empty($this->filters['meeting_type_id'])) {
            $query->whereHas('meeting', function ($q) {
                $q->where('meeting_type_id', $this->filters['meeting_type_id']);
            });
        }

        return $query->skip(($page - 1) * $limit)->take($limit)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tên người dự',
            'Chức vụ',
            'Cuộc họp',
            'Loại cuộc họp',
            'Vai trò',
            'Ký danh',
            'Trạng thái',
            'Ghi chú',
            'Giờ điểm danh',
        ];
    }

    public function map($row): array
    {
        $roleLabels = [
            'chair' => 'Chủ tọa',
            'secretary' => 'Thư ký',
            'delegate' => 'Đại biểu'
        ];
        $statusLabels = [
            'pending' => 'Chưa điểm danh',
            'present' => 'Có mặt',
            'absent' => 'Vắng mặt'
        ];
        
        return [
            $row->id,
            $row->user?->name ?? '---',
            $row->position,
            $row->meeting?->title ?? '---',
            $row->meeting?->meetingType?->name ?? '---',
            $roleLabels[$row->meeting_role] ?? $row->meeting_role,
            $row->attendance_type === 'physical' ? 'Họp trực tiếp' : 'Họp Online',
            $statusLabels[$row->attendance_status] ?? $row->attendance_status,
            $row->absence_reason,
            $row->checkin_at?->format('H:i:s d/m/Y') ?? '---',
        ];
    }
}
