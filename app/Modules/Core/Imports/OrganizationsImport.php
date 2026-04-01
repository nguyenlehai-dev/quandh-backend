<?php

namespace App\Modules\Core\Imports;

use App\Modules\Core\Enums\StatusEnum;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Services\OrganizationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrganizationsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $service = app(OrganizationService::class);
        
        foreach ($rows as $row) {
            $name = $row['name'] ?? $row['name_'] ?? '';
            if (!$name) {
                continue;
            }

            $parentSlug = $row['parent_slug'] ?? $row['parent slug'] ?? '';
            $parent = $parentSlug ? Organization::where('slug', $parentSlug)->first() : null;
            
            if (!$parent && !empty($row['parent_id'])) {
                $parent = Organization::find($row['parent_id']);
            }

            $status = $row['status'] ?? StatusEnum::Active->value;
            $status = in_array($status, StatusEnum::values()) ? $status : StatusEnum::Active->value;

            $org = null;
            if (!empty($row['id'])) {
                $org = Organization::find($row['id']);
            }
            if (!$org && !empty($row['slug'])) {
                $org = Organization::where('slug', $row['slug'])->first();
            }

            $data = [
                'name' => $name,
                'description' => $row['description'] ?? null,
                'status' => $status,
                'parent_id' => $parent?->id,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];

            if (!$org) {
                $data['slug'] = $service->generateUniqueSlug($row['slug'] ?? Str::slug($name));
                Organization::create($data);
            } else {
                $org->update($data);
            }
        }
    }
}
