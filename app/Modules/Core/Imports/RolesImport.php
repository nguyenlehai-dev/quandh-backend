<?php

namespace App\Modules\Core\Imports;

use App\Modules\Core\Models\Role;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RolesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = $row['name'] ?? $row['name_'] ?? '';
            if (!$name) {
                continue;
            }

            $guard = $row['guard_name'] ?? 'api';
            $organizationId = isset($row['organization_id']) ? (int) $row['organization_id'] : null;

            $role = null;
            if (!empty($row['id'])) {
                $role = Role::find($row['id']);
            }
            if (!$role) {
                $role = Role::where('name', $name)->where('guard_name', $guard)->first();
            }

            $data = [
                'name' => $name,
                'guard_name' => $guard,
                'organization_id' => $organizationId ?: null,
            ];

            if (!$role) {
                Role::create($data);
            } else {
                $role->update($data);
            }
        }
    }
}
