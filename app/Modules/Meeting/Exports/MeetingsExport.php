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

    /**
     * Xuất theo bộ lọc của index, đầy đủ trường như MeetingResource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $meetings = Meeting::with(['creator', 'editor'])
            ->withCount(['participants', 'agendas', 'conclusions'])
            ->filter($this->filters)
            ->get();

        return $meetings->map(fn ($meeting) => [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'description' => $meeting->description,
            'location' => $meeting->location,
            'start_at' => $meeting->start_at?->format('H:i:s d/m/Y'),
            'end_at' => $meeting->end_at?->format('H:i:s d/m/Y'),
            'status' => $meeting->status,
            'participants_count' => $meeting->participants_count,
            'agendas_count' => $meeting->agendas_count,
            'conclusions_count' => $meeting->conclusions_count,
            'created_by' => $meeting->creator?->name ?? 'N/A',
            'updated_by' => $meeting->editor?->name ?? 'N/A',
            'created_at' => $meeting->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $meeting->updated_at?->format('H:i:s d/m/Y'),
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tiêu đề',
            'Mô tả',
            'Địa điểm',
            'Bắt đầu',
            'Kết thúc',
            'Trạng thái',
            'Số thành viên',
            'Số mục nghị sự',
            'Số kết luận',
            'Người tạo',
            'Người cập nhật',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }
}
