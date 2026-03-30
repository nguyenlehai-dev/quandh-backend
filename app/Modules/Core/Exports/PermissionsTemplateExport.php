<?php

namespace App\Modules\Core\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PermissionsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'id', 
            'name', 
            'guard_name', 
            'description', 
            'sort_order', 
            'parent_id'
        ];
    }

    public function array(): array
    {
        return [
            [
                '', 
                'permissions.view_sample', 
                'web', 
                'Quyền xem mẫu', 
                '1', 
                ''
            ],
        ];
    }
}
