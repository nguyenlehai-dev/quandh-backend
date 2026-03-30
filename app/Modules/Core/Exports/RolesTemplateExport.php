<?php

namespace App\Modules\Core\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RolesTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'id', 
            'name', 
            'guard_name', 
            'organization_id'
        ];
    }

    public function array(): array
    {
        return [
            [
                '', 
                'Vai trò mẫu', 
                'web', 
                '1'
            ],
        ];
    }
}
