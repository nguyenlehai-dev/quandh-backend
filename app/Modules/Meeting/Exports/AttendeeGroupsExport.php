<?php

namespace App\Modules\Meeting\Exports;

use App\Modules\Meeting\Models\AttendeeGroup;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendeeGroupsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $limit = isset($this->filters['limit']) ? (int) $this->filters['limit'] : 1000;
        $page = isset($this->filters['page']) ? (int) $this->filters['page'] : 1;
        
        $query = AttendeeGroup::query()
            ->with(['meetingType'])
            ->withCount('members')
            ->orderBy('id', 'desc');

        if (!empty($this->filters['search'])) {
            $query->where('name', 'like', "%{$this->filters['search']}%");
        }
        if (!empty($this->filters['meeting_type_id'])) {
            $query->where('meeting_type_id', $this->filters['meeting_type_id']);
        }

        return $query->skip(($page - 1) * $limit)->take($limit)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Loại cuộc họp',
            'Tên nhóm',
            'Mô tả',
            'Trạng thái',
            'Số lượng TV',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->meetingType?->name ?? '---',
            $row->name,
            $row->description,
            $row->status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động',
            $row->members_count,
            $row->created_at?->format('H:i:s d/m/Y'),
            $row->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
