<?php

namespace App\Modules\Meeting\Imports;

use App\Modules\Meeting\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MeetingsImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private readonly int $organizationId
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $title = $row['title'] ?? $row['tieu_de'] ?? null;
            $startAt = $this->date($row['start_at'] ?? $row['bat_dau'] ?? null);

            if (! $title || ! $startAt) {
                continue;
            }

            $code = $row['code'] ?? $row['ma_cuoc_hop'] ?? null;
            $payload = [
                'organization_id' => $this->organizationId,
                'meeting_type_id' => $row['meeting_type_id'] ?? null,
                'code' => $code ?: 'MTG-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'title' => $title,
                'description' => $row['description'] ?? $row['mo_ta'] ?? null,
                'location' => $row['location'] ?? $row['dia_diem'] ?? null,
                'start_at' => $startAt,
                'end_at' => $this->date($row['end_at'] ?? $row['ket_thuc'] ?? null),
                'status' => $row['status'] ?? 'draft',
                'qr_token' => $row['qr_token'] ?? (string) Str::uuid(),
            ];

            Meeting::query()->updateOrCreate(
                ['organization_id' => $this->organizationId, 'code' => $payload['code']],
                $payload
            );
        }
    }

    protected function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
