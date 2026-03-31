<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Enums\StatusEnum;
use App\Modules\Core\Exports\OrganizationsExport;
use App\Modules\Core\Imports\OrganizationsImport;
use App\Modules\Core\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationService
{
    public function publicList(array $filters = []): Collection
    {
        $publicFilters = [
            ...$filters,
            'status' => StatusEnum::Active->value,
            'sort_by' => 'sort_order',
            'sort_order' => 'asc',
        ];

        return $this->getFlatTreeOrdered($publicFilters);
    }

    public function publicOptions(array $filters = []): Collection
    {
        $publicFilters = [
            ...$filters,
            'status' => StatusEnum::Active->value,
            'sort_by' => 'sort_order',
            'sort_order' => 'asc',
        ];

        return Organization::query()
            ->select(['id', 'name', 'description'])
            ->filter($publicFilters)
            ->treeOrder()
            ->get();
    }

    public function stats(array $filters): array
    {
        $base = Organization::filter($filters);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', StatusEnum::Active->value)->count(),
            'inactive' => (clone $base)->where('status', '!=', StatusEnum::Active->value)->count(),
        ];
    }

    public function index(array $filters, int $limit)
    {
        $all = Organization::with(['creator', 'editor', 'parent'])->filter($filters)->get();
        
        // If searching or custom sorting, do not build a tree (prevent orphans)
        if (!empty($filters['search']) || !empty($filters['status']) || (!empty($filters['sort_by']) && $filters['sort_by'] !== 'sort_order')) {
            $result = $all;
        } else {
            $tree = $this->buildTree($all);
            $result = collect();
            $flatten = function ($nodes) use (&$flatten, &$result) {
                foreach ($nodes as $node) {
                    $result->push($node);
                    $flatten($node->children);
                }
            };
            $flatten($tree);
        }

        // Calculate pagination parameters
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        
        // Return a length-aware paginator based on the flattened Collection
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $result->forPage($page, $limit)->values(),
            $result->count(),
            $limit,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
    }

    public function tree(?string $status)
    {
        $query = Organization::query()
            ->when($status, fn ($q, $value) => $q->where('status', $value));
        $items = $query->orderBy('sort_order')->orderBy('id')->get();

        return $this->buildTree($items);
    }

    public function show(Organization $organization): Organization
    {
        return $organization->load(['creator', 'editor', 'parent', 'children' => fn ($q) => $q->orderBy('sort_order')]);
    }

    public function store(array $data): Organization
    {
        return Organization::create($data);
    }

    public function update(Organization $organization, array $data): array
    {
        if (isset($data['parent_id']) && (int) $data['parent_id'] !== 0) {
            if ($this->isDescendantOf($organization->id, (int) $data['parent_id'])) {
                return [
                    'ok' => false,
                    'message' => 'Không thể chọn organization con làm organization cha.',
                    'code' => 422,
                    'error_code' => 'CONFLICT',
                ];
            }
        }

        if (array_key_exists('parent_id', $data) && (int) $data['parent_id'] === 0) {
            $data['parent_id'] = null;
        }

        $organization->update($data);

        return [
            'ok' => true,
            'organization' => $organization->fresh(['parent', 'children']),
        ];
    }

    public function destroy(Organization $organization): void
    {
        $organization->delete();
    }

    public function bulkDestroy(array $ids): void
    {
        Organization::whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): void
    {
        if ($status !== StatusEnum::Active->value) {
            $this->guardAgainstInvalidDeactivation($ids);
        }

        Organization::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function changeStatus(Organization $organization, string $status): Organization
    {
        if ($status !== StatusEnum::Active->value) {
            $this->guardAgainstInvalidDeactivation([$organization->id]);
        }

        $organization->update(['status' => $status]);

        return $organization->load(['parent', 'children']);
    }

    public function export(array $filters): BinaryFileResponse
    {
        return Excel::download(new OrganizationsExport($filters), 'organizations.xlsx');
    }

    public function import($file): void
    {
        Excel::import(new OrganizationsImport, $file);
    }

    public function getFlatTreeOrdered(array $filters = []): Collection
    {
        $all = Organization::with(['creator', 'editor'])->filter($filters)->get();
        $tree = $this->buildTree($all);
        $result = collect();
        $flatten = function ($nodes) use (&$flatten, &$result) {
            foreach ($nodes as $node) {
                $result->push($node);
                $flatten($node->children);
            }
        };
        $flatten($tree);

        return $result;
    }

    public function getDepth(Organization $organization): int
    {
        $depth = 0;
        $parentId = $organization->parent_id;
        $ids = [$organization->id];

        while ($parentId) {
            if (in_array($parentId, $ids)) {
                break;
            }

            $ids[] = $parentId;
            $parent = Organization::find($parentId);
            $parentId = $parent ? $parent->parent_id : null;
            $depth++;
        }

        return $depth;
    }

    public function generateUniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $query = Organization::where('slug', $slug);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $index = 0;
        while ($query->exists()) {
            $slug = $base.'-'.(++$index);
            $query = Organization::where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    public function buildTree(Collection $items): Collection
    {
        $grouped = $items->groupBy('parent_id');
        $builder = function ($parentId) use ($grouped, &$builder) {
            return ($grouped->get($parentId) ?? collect())
                ->map(function ($node) use (&$builder) {
                    $node->setRelation('children', $builder($node->id));

                    return $node;
                })
                ->values();
        };

        return $builder(null);
    }

    private function isDescendantOf(int $candidateId, int $id): bool
    {
        $current = Organization::find($id);

        while ($current && $current->parent_id) {
            if ($current->parent_id === $candidateId) {
                return true;
            }

            $current = Organization::find($current->parent_id);
        }

        return false;
    }

    private function guardAgainstInvalidDeactivation(array $organizationIds): void
    {
        $ids = collect($organizationIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return;
        }

        $currentOrganizationId = (int) request()->header('X-Organization-Id');

        if ($currentOrganizationId && in_array($currentOrganizationId, $ids, true)) {
            throw ValidationException::withMessages([
                'status' => ['Không thể chuyển tổ chức đang làm việc hiện tại sang ngừng hoạt động.'],
            ]);
        }

        $remainingActiveCount = Organization::query()
            ->where('status', StatusEnum::Active->value)
            ->whereNotIn('id', $ids)
            ->count();

        if ($remainingActiveCount === 0) {
            throw ValidationException::withMessages([
                'status' => ['Hệ thống phải luôn có ít nhất một tổ chức đang hoạt động.'],
            ]);
        }
    }
}
