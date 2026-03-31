<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\MeetingType;

class MeetingTypeService
{
    public function __construct(private MeetingType $model) {}

    public function index(array $params = [])
    {
        $sortBy = in_array($params['sort_by'] ?? null, ['id', 'name', 'status', 'created_at', 'updated_at'], true)
            ? $params['sort_by']
            : 'created_at';
        $sortOrder = ($params['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->model
            ->withCount(['attendeeGroups', 'documentTypes', 'meetings'])
            ->when(!empty($params['search']), fn ($q) => $q->where('name', 'like', "%{$params['search']}%"))
            ->when(!empty($params['status']), fn ($q) => $q->where('status', $params['status']))
            ->orderBy($sortBy, $sortOrder)
            ->paginate($params['limit'] ?? 10);
    }

    public function export(array $filters)
    {
        // Require the Maatwebsite Excel facade dynamically or just use the facade
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Meeting\Exports\MeetingTypesExport($filters),
            'loai-cuoc-hop.xlsx'
        );
    }

    public function store(array $data)
    {
        return $this->model->create($data);
    }

    public function update(MeetingType $type, array $data)
    {
        $type->update($data);
        return $type;
    }

    public function destroy(MeetingType $type)
    {
        return $type->delete();
    }

    public function bulkDestroy(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function bulkUpdate(array $ids, array $data)
    {
        return $this->model->whereIn('id', $ids)->update($data);
    }

    public function changeStatus(MeetingType $type, string $status): MeetingType
    {
        $type->update(['status' => $status]);

        return $type->refresh()->loadCount(['attendeeGroups', 'documentTypes', 'meetings']);
    }
}
