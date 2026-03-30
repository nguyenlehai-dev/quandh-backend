<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\AttendeeGroup;
use App\Modules\Meeting\Resources\AttendeeGroupResource;
use App\Modules\Meeting\Services\AttendeeGroupService;
use Illuminate\Http\Request;

class AttendeeGroupController extends Controller
{
    public function __construct(private AttendeeGroupService $service) {}

    public function index(Request $request)
    {
        $items = $this->service->index($request->all());
        return $this->successCollection(AttendeeGroupResource::collection($items));
    }

    public function export(Request $request)
    {
        return $this->service->export($request->all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'meeting_type_id' => 'nullable|integer|exists:m_meeting_types,id',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);
        $item = $this->service->store($validated);
        return $this->successResource(new AttendeeGroupResource($item), 'Tạo nhóm người dự họp thành công!', 201);
    }

    public function update(Request $request, AttendeeGroup $attendee_group)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'meeting_type_id' => 'nullable|integer|exists:m_meeting_types,id',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);
        $item = $this->service->update($attendee_group, $validated);
        return $this->successResource(new AttendeeGroupResource($item), 'Cập nhật nhóm người dự họp thành công!');
    }

    public function destroy(AttendeeGroup $attendee_group)
    {
        $this->service->destroy($attendee_group);
        return $this->success(null, 'Xóa nhóm người dự họp thành công!');
    }

    /** Thêm thành viên vào nhóm. */
    public function addMember(Request $request, AttendeeGroup $attendee_group)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'position' => 'nullable|string|max:255',
        ]);
        $item = $this->service->addMember($attendee_group, $validated['user_id'], $validated['position'] ?? null);
        return $this->successResource(new AttendeeGroupResource($item), 'Đã thêm thành viên vào nhóm!');
    }

    /** Xóa thành viên khỏi nhóm. */
    public function removeMember(AttendeeGroup $attendee_group, int $userId)
    {
        $item = $this->service->removeMember($attendee_group, $userId);
        return $this->successResource(new AttendeeGroupResource($item), 'Đã xóa thành viên khỏi nhóm!');
    }
}
