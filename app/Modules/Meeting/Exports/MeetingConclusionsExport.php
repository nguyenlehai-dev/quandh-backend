<?php

namespace App\Modules\Meeting\Exports;

use App\Modules\Meeting\Models\MeetingConclusion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeetingConclusionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $limit = isset($this->filters['limit']) ? (int) $this->filters['limit'] : 1000;
        $page = isset($this->filters['page']) ? (int) $this->filters['page'] : 1;
        
        $query = MeetingConclusion::query()
            ->with(['meeting:id,title,meeting_type_id', 'meeting.meetingType', 'agenda:id,title', 'creator'])
            ->has('meeting')
            ->orderBy('id', 'desc');

        if (!empty($this->filters['search'])) {
            $query->where('title', 'like', "%{$this->filters['search']}%")
                  ->orWhere('content', 'like', "%{$this->filters['search']}%");
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
            'Tiêu đề kết luận',
            'Nội dung kết luận',
            'Cuộc họp',
            'Loại cuộc họp',
            'Mục nghị sự',
            'Người tạo',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->title,
            $row->content,
            $row->meeting?->title ?? '---',
            $row->meeting?->meetingType?->name ?? '---',
            $row->agenda?->title ?? '---',
            $row->creator?->name ?? '---',
            $row->created_at?->format('H:i:s d/m/Y'),
            $row->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
