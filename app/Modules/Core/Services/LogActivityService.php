<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Exports\LogActivitiesExport;
use App\Modules\Core\Models\LogActivity;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogActivityService
{
    public function stats(array $filters): array
    {
        $base = LogActivity::filter($filters);

        $view = (clone $base)->where('method_type', 'GET')->count();
        $create = (clone $base)->where('method_type', 'POST')->count();
        $update = (clone $base)->whereIn('method_type', ['PUT', 'PATCH'])->count();
        $delete = (clone $base)->where('method_type', 'DELETE')->count();

        return [
            'total' => (clone $base)->count(),
            'view' => $view,
            'create' => $create,
            'update' => $update,
            'delete' => $delete,
        ];
    }

    public function index(array $filters, int $limit)
    {
        return LogActivity::with('user', 'organization')
            ->filter($filters)
            ->paginate($limit);
    }

    public function show(LogActivity $logActivity): LogActivity
    {
        return $logActivity->load('user', 'organization');
    }

    public function destroy(LogActivity $logActivity): void
    {
        $logActivity->delete();
    }

    public function bulkDestroy(array $ids): int
    {
        return LogActivity::whereIn('id', $ids)->delete();
    }

    public function destroyByDate(string $fromDate, string $toDate): int
    {
        return LogActivity::whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->delete();
    }

    public function destroyAll(): int
    {
        $count = LogActivity::count();
        LogActivity::truncate();

        return $count;
    }

    public function export(array $filters): BinaryFileResponse
    {
        return Excel::download(new LogActivitiesExport($filters), 'log-activities.xlsx');
    }
}
