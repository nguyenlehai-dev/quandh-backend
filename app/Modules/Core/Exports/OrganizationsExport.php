<?php

namespace App\Modules\Core\Exports;

use App\Modules\Core\Services\OrganizationService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrganizationsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection()
    {
        $service = app(OrganizationService::class);
        $items = $service->getFlatTreeOrdered($this->filters);

        // Build a lookup map for parent slugs and depth (avoid N+1 queries)
        $allOrgs = $items->keyBy('id');

        $getDepth = function ($org) use ($allOrgs) {
            $depth = 0;
            $parentId = $org->parent_id;
            $visited = [$org->id];

            while ($parentId) {
                if (in_array($parentId, $visited)) break;
                $visited[] = $parentId;
                $parent = $allOrgs->get($parentId);
                $parentId = $parent ? $parent->parent_id : null;
                $depth++;
            }

            return $depth;
        };

        if (!empty($this->filters['limit'])) {
            $limit = (int) $this->filters['limit'];
            $page = !empty($this->filters['page']) ? (int) $this->filters['page'] : 1;
            $items = $items->skip(($page - 1) * $limit)->take($limit);
        }

        return $items->map(fn ($o) => [
            'id' => $o->id,
            'name' => $o->name,
            'slug' => $o->slug,
            'description' => $o->description,
            'status' => $o->status,
            'parent_id' => $o->parent_id,
            'parent_slug' => $o->parent_id ? ($allOrgs->get($o->parent_id)?->slug ?? '') : '',
            'sort_order' => $o->sort_order,
            'depth' => $getDepth($o),
            'created_by' => $o->creator?->name ?? 'N/A',
            'updated_by' => $o->editor?->name ?? 'N/A',
            'created_at' => $o->created_at?->format('H:i:s d/m/Y'),
            'updated_at' => $o->updated_at?->format('H:i:s d/m/Y'),
        ]);
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Slug', 'Description', 'Status', 'Parent ID', 'Parent Slug', 'Sort Order', 'Depth', 'Created By', 'Updated By', 'Created At', 'Updated At'];
    }
}
