<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\MeetingType;

class MeetingTypeService
{
    public function __construct(private MeetingType $model) {}

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

    public function update(MeetingType $type, array $data)
    {
        $type->update($data);
        return $type;
    }

    public function destroy(MeetingType $type)
    {
        return $type->delete();
    }
}
