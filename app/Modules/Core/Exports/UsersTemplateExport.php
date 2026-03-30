<?php

namespace App\Modules\Core\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'id', 
            'name', 
            'email', 
            'user_name', 
            'password', 
            'status'
        ];
    }

    public function array(): array
    {
        return [
            [
                '', 
                'Cán bộ mẫu', 
                'canbomau@example.com', 
                'canbomau', 
                '123456', 
                'active'
            ],
        ];
    }
}
