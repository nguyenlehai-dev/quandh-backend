<?php

namespace App\Modules\Meeting\Exports;

use App\Modules\Meeting\Models\MeetingType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeetingTypesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $limit = isset($this->filters['limit']) ? (int) $this->filters['limit'] : 1000;
        $page = isset($this->filters['page']) ? (int) $this->filters['page'] : 1;
        
        $query = MeetingType::query()
            ->withCount(['attendeeGroups', 'documentTypes', 'meetings'])
            ->orderBy('id', 'desc');

        if (!empty($this->filters['search'])) {
            $query->where('name', 'like', "%{$this->filters['search']}%");
        }

        return $query->skip(($page - 1) * $limit)->take($limit)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tên phân loại',
            'Mô tả',
            'Trạng thái',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->description,
            $row->status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động',
            $row->created_at?->format('H:i:s d/m/Y'),
            $row->updated_at?->format('H:i:s d/m/Y'),
        ];
    }
}
