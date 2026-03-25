<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\AttendeeGroup;

class AttendeeGroupService
{
    public function __construct(private AttendeeGroup $model) {}

    public function index($params = [])
    {
        return $this->model
            ->when(!empty($params['search']), fn ($q) => $q->where('name', 'like', "%{$params['search']}%"))
            ->orderBy($params['sort_by'] ?? 'created_at', $params['sort_order'] ?? 'desc')
            ->paginate($params['limit'] ?? 10);
    }

    public function store(array $data)
    {
        return $this->model->create($data);
    }

    public function update(AttendeeGroup $group, array $data)
    {
        $group->update($data);
        return $group;
    }

    public function destroy(AttendeeGroup $group)
    {
        return $group->delete();
    }
}
