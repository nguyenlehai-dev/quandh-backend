<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Document\Exports\CatalogExport;
use App\Modules\Document\Imports\CatalogImport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MeetingCatalogService
{
    public function publicCatalog(string $modelClass, array $filters)
    {
        /** @var Model $model */
        $model = app($modelClass);

        return $model->newQuery()
            ->where('status', 'active')
            ->filter([
                ...$filters,
                'sort_by' => $filters['sort_by'] ?? 'name',
                'sort_order' => $filters['sort_order'] ?? 'asc',
            ])
            ->get();
    }

    public function publicOptions(string $modelClass, array $filters)
    {
        /** @var Model $model */
        $model = app($modelClass);

        return $model->newQuery()
            ->select(['id', 'name', 'description'])
            ->where('status', 'active')
            ->filter([
                ...$filters,
                'sort_by' => $filters['sort_by'] ?? 'name',
                'sort_order' => $filters['sort_order'] ?? 'asc',
            ])
            ->get();
    }

    public function stats(string $modelClass, array $filters): array
    {
        $base = $this->query($modelClass, $filters);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
        ];
    }

    public function index(string $modelClass, array $filters, int $limit)
    {
        return $this->query($modelClass, $filters)
            ->with(['creator', 'editor'])
            ->paginate($limit);
    }

    public function show(Model $model): Model
    {
        return $model->load(['creator', 'editor']);
    }

    public function resolve(string $modelClass, mixed $id): Model
    {
        /** @var Model $model */
        $model = app($modelClass);

        $resolved = $this->query($modelClass)->whereKey($id)->first();

        if (! $resolved) {
            throw (new ModelNotFoundException)->setModel($modelClass, [$id]);
        }

        return $resolved;
    }

    public function store(string $modelClass, array $validated): Model
    {
        /** @var Model $model */
        $model = app($modelClass);

        $validated['organization_id'] = $this->organizationId();

        return $model->newQuery()->create($validated)->load(['creator', 'editor']);
    }

    public function update(Model $model, array $validated): Model
    {
        $this->ensureOrganizationScope($model);
        $model->update($validated);

        return $model->load(['creator', 'editor']);
    }

    public function destroy(Model $model): void
    {
        $this->ensureOrganizationScope($model);
        $model->delete();
    }

    public function bulkDestroy(string $modelClass, array $ids): void
    {
        $this->query($modelClass)->whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(string $modelClass, array $ids, string $status): void
    {
        $this->query($modelClass)->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function changeStatus(Model $model, string $status): Model
    {
        $this->ensureOrganizationScope($model);
        $model->update(['status' => $status]);

        return $model->load(['creator', 'editor']);
    }

    public function export(string $modelClass, array $filters, string $fileName): BinaryFileResponse
    {
        return Excel::download(new CatalogExport($modelClass, $filters), $fileName);
    }

    public function import(string $modelClass, $file): void
    {
        Excel::import(new CatalogImport($modelClass), $file);
    }

    private function query(string $modelClass, array $filters = [])
    {
        /** @var Model $model */
        $model = app($modelClass);

        return $model->newQuery()
            ->when($this->organizationId(), fn ($q, $orgId) => $q->where('organization_id', $orgId))
            ->filter($filters);
    }

    private function organizationId(): ?int
    {
        return request()->header('X-Organization-Id') ? (int) request()->header('X-Organization-Id') : null;
    }

    private function ensureOrganizationScope(Model $model): void
    {
        if (! isset($model->organization_id)) {
            return;
        }

        $organizationId = $this->organizationId();
        if ($organizationId && (int) $model->organization_id !== $organizationId) {
            abort(403, 'Bạn không có quyền truy cập tài nguyên này.');
        }
    }
}
