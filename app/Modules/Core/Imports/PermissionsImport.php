<?php

namespace App\Modules\Core\Imports;

use App\Modules\Core\Models\Permission;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PermissionsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = $row['name'] ?? $row['name_'] ?? '';
            if (!$name) {
                continue;
            }

            $guard = $row['guard_name'] ?? 'api';
            $parentId = isset($row['parent_id']) && $row['parent_id'] !== '' ? (int) $row['parent_id'] : null;

            $permission = null;
            if (!empty($row['id'])) {
                $permission = Permission::find($row['id']);
            }
            if (!$permission) {
                $permission = Permission::where('name', $name)->where('guard_name', $guard)->first();
            }

            $data = [
                'name' => $name,
                'guard_name' => $guard,
                'description' => $row['description'] ?? null,
                'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
                'parent_id' => $parentId,
            ];

            if (!$permission) {
                Permission::create($data);
            } else {
                $permission->update($data);
            }
        }
    }
}
