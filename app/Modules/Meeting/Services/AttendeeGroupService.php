<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\AttendeeGroup;

class AttendeeGroupService
{
    public function __construct(private AttendeeGroup $model) {}

    public function index(array $params = [])
    {
        $sortBy = in_array($params['sort_by'] ?? null, ['id', 'name', 'status', 'meeting_type_id', 'created_at', 'updated_at'], true)
            ? $params['sort_by']
            : 'created_at';
        $sortOrder = ($params['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->model
            ->with(['meetingType:id,name', 'members:id,name,email'])
            ->when(!empty($params['search']), fn ($q) => $q->where('name', 'like', "%{$params['search']}%"))
            ->when(!empty($params['status']), fn ($q) => $q->where('status', $params['status']))
            ->when(!empty($params['meeting_type_id']), fn ($q) => $q->where('meeting_type_id', $params['meeting_type_id']))
            ->orderBy($sortBy, $sortOrder)
            ->paginate($params['limit'] ?? 10);
    }

    public function export(array $filters)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Meeting\Exports\AttendeeGroupsExport($filters),
            'nhom-nguoi-du-hop.xlsx'
        );
    }

    public function store(array $data)
    {
        $group = $this->model->create($data);

        // Gắn thành viên nếu có
        if (!empty($data['member_ids'])) {
            $group->members()->sync($data['member_ids']);
        }

        return $group->load(['meetingType:id,name', 'members:id,name,email']);
    }

    public function update(AttendeeGroup $group, array $data)
    {
        $group->update($data);

        // Cập nhật danh sách thành viên nếu có
        if (array_key_exists('member_ids', $data)) {
            $group->members()->sync($data['member_ids'] ?? []);
        }

        return $group->load(['meetingType:id,name', 'members:id,name,email']);
    }

    public function destroy(AttendeeGroup $group)
    {
        return $group->delete();
    }

    public function changeStatus(AttendeeGroup $group, string $status): AttendeeGroup
    {
        $group->update(['status' => $status]);

        return $group->load(['meetingType:id,name', 'members:id,name,email']);
    }

    /** Thêm thành viên vào nhóm. */
    public function addMember(AttendeeGroup $group, int $userId, ?string $position = null)
    {
        $group->members()->syncWithoutDetaching([
            $userId => ['position' => $position],
        ]);

        return $group->load('members:id,name,email');
    }

    /** Xóa thành viên khỏi nhóm. */
    public function removeMember(AttendeeGroup $group, int $userId)
    {
        $group->members()->detach($userId);

        return $group->load('members:id,name,email');
    }
}
