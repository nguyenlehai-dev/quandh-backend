<?php

namespace App\Modules\Meeting\Imports;

use App\Modules\Meeting\Enums\MeetingStatusEnum;
use App\Modules\Meeting\Models\Meeting;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MeetingsImport implements ToModel, WithHeadingRow
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Meeting([
            'title' => $row['title'] ?? $row['tiêu đề'] ?? '',
            'description' => $row['description'] ?? $row['mô tả'] ?? null,
            'location' => $row['location'] ?? $row['địa điểm'] ?? null,
            'status' => $row['status'] ?? $row['trạng thái'] ?? MeetingStatusEnum::Draft->value,
        ]);
    }
}
