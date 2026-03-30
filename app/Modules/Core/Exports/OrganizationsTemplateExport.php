<?php

namespace App\Modules\Core\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrganizationsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'id', 
            'name', 
            'slug', 
            'description', 
            'status', 
            'parent_id', 
            'parent_slug', 
            'sort_order'
        ];
    }

    public function array(): array
    {
        return [
            [
                '', 
                'Tổ chức mẫu', 
                'to-chuc-mau', 
                'Mô tả tổ chức mẫu', 
                'active', 
                '', 
                '', 
                '1'
            ],
        ];
    }
}
