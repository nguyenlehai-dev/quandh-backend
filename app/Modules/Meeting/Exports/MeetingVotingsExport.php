<?php

namespace App\Modules\Meeting\Exports;

use App\Modules\Meeting\Models\MeetingVoting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeetingVotingsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $limit = isset($this->filters['limit']) ? (int) $this->filters['limit'] : 1000;
        $page = isset($this->filters['page']) ? (int) $this->filters['page'] : 1;
        
        $query = MeetingVoting::query()
            ->with(['meeting:id,title,meeting_type_id', 'meeting.meetingType', 'agenda:id,title'])
            ->withCount('results')
            ->has('meeting')
            ->orderBy('id', 'desc');

        if (!empty($this->filters['search'])) {
            $query->where('title', 'like', "%{$this->filters['search']}%");
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
            'Tiêu đề biểu quyết',
            'Cuộc họp',
            'Loại cuộc họp',
            'Mục nghị sự',
            'Loại biểu quyết',
            'Trạng thái',
            'Tổng số phiếu',
            'Ngày tạo',
        ];
    }

    public function map($row): array
    {
        $type = $row->type === 'anonymous' ? 'Ẩn danh' : 'Công khai';
        $statusLabels = [
            'pending' => 'Chờ mở',
            'open' => 'Đang mở',
            'closed' => 'Đã đóng'
        ];
        
        return [
            $row->id,
            $row->title,
            $row->meeting?->title ?? '---',
            $row->meeting?->meetingType?->name ?? '---',
            $row->agenda?->title ?? '---',
            $type,
            $statusLabels[$row->status] ?? $row->status,
            $row->results_count,
            $row->created_at?->format('H:i:s d/m/Y'),
        ];
    }
}
