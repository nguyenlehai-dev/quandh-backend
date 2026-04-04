<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\AttendeeGroup;
use App\Modules\Meeting\Models\AttendeeGroupMember;
use App\Modules\Meeting\Resources\AttendeeGroupMemberResource;
use App\Modules\Meeting\Services\AttendeeGroupMemberService;
use Illuminate\Http\Request;

class AttendeeGroupMemberController extends Controller
{
    public function __construct(private AttendeeGroupMemberService $service) {}

    public function index(AttendeeGroup $attendeeGroup)
    {
        return $this->success(
            AttendeeGroupMemberResource::collection($this->service->index($attendeeGroup))
        );
    }

    public function store(Request $request, AttendeeGroup $attendeeGroup)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'position' => 'nullable|string|max:255',
        ]);

        $member = $this->service->store($attendeeGroup, $validated);

        return $this->successResource(new AttendeeGroupMemberResource($member), 'Đã thêm thành viên vào nhóm.', 201);
    }

    public function update(Request $request, AttendeeGroup $attendeeGroup, AttendeeGroupMember $member)
    {
        abort_unless((int) $member->attendee_group_id === (int) $attendeeGroup->id, 422, 'Thành viên không thuộc nhóm này.');

        $validated = $request->validate([
            'position' => 'nullable|string|max:255',
        ]);

        $member = $this->service->update($member, $validated);

        return $this->successResource(new AttendeeGroupMemberResource($member), 'Đã cập nhật thành viên nhóm.');
    }

    public function destroy(AttendeeGroup $attendeeGroup, AttendeeGroupMember $member)
    {
        abort_unless((int) $member->attendee_group_id === (int) $attendeeGroup->id, 422, 'Thành viên không thuộc nhóm này.');

        $this->service->destroy($member);

        return $this->success(null, 'Đã xóa thành viên khỏi nhóm.');
    }
}
