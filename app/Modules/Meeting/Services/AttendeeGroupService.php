<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\AttendeeGroup;

class AttendeeGroupService
{
    public function __construct(private AttendeeGroup $model) {}

    public function index($params = [])
    {
        return $this->model
            ->with(['meetingType:id,name', 'members:id,name,email'])
            ->when(!empty($params['search']), fn ($q) => $q->where('name', 'like', "%{$params['search']}%"))
            ->when(!empty($params['meeting_type_id']), fn ($q) => $q->where('meeting_type_id', $params['meeting_type_id']))
            ->orderBy($params['sort_by'] ?? 'created_at', $params['sort_order'] ?? 'desc')
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
