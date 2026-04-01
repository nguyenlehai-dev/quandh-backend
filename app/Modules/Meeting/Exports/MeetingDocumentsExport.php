<?php

namespace App\Modules\Meeting\Exports;

use App\Modules\Meeting\Models\MeetingDocument;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MeetingDocumentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $limit = isset($this->filters['limit']) ? (int) $this->filters['limit'] : 1000;
        $page = isset($this->filters['page']) ? (int) $this->filters['page'] : 1;
        
        $query = MeetingDocument::query()
            ->with([
                'meeting:id,title,meeting_type_id',
                'meeting.meetingType',
                'documentType',
                'documentField',
                'issuingAgency',
                'documentSigner',
            ])
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
            'Tên tài liệu',
            'Loại tài liệu',
            'Cơ quan ban hành',
            'Lĩnh vực',
            'Người ký',
            'Cuộc họp chứa',
            'Mô tả',
            'Ngày tạo',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->title,
            $row->documentType?->name ?? '---',
            $row->issuingAgency?->name ?? '---',
            $row->documentField?->name ?? '---',
            $row->documentSigner?->name ?? '---',
            $row->meeting?->title ?? '---',
            $row->description,
            $row->created_at?->format('H:i:s d/m/Y'),
        ];
    }
}
