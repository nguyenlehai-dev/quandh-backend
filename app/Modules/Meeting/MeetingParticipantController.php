<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Resources\MeetingParticipantResource;
use App\Modules\Meeting\Services\MeetingParticipantService;
use Illuminate\Http\Request;

/**
 * @group Meeting - Thành viên cuộc họp
 * @header X-Organization-Id ID tổ chức cần làm việc. Example: 1
 *
 * Quản lý thành viên: gán, xóa, cập nhật vai trò, điểm danh.
 */
class MeetingParticipantController extends Controller
{
    public function __construct(private MeetingParticipantService $service) {}

    /**
     * Danh sách thành viên
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function index(Meeting $meeting)
    {
        $participants = $this->service->index($meeting);

        return $this->success(MeetingParticipantResource::collection($participants));
    }

    /**
     * Gán thành viên mới
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam user_id integer required ID người dùng. Example: 2
     * @bodyParam position string Chức vụ trong cuộc họp. Example: Giám đốc
     * @bodyParam meeting_role string Vai trò: chair, secretary, delegate. Example: delegate
     */
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'position' => 'nullable|string|max:255',
            'meeting_role' => 'sometimes|in:chair,secretary,delegate',
        ], [
            'user_id.required' => 'Vui lòng chọn người dùng.',
            'user_id.exists' => 'Người dùng không tồn tại.',
        ]);

        $participant = $this->service->store($meeting, $validated);

        return $this->successResource(new MeetingParticipantResource($participant), 'Đã gán thành viên thành công!', 201);
    }

    /**
     * Cập nhật thông tin thành viên
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam participant integer required ID thành viên. Example: 1
     *
     * @bodyParam position string Chức vụ.
     * @bodyParam meeting_role string Vai trò: chair, secretary, delegate.
     */
    public function update(Request $request, Meeting $meeting, MeetingParticipant $participant)
    {
        $validated = $request->validate([
            'position' => 'nullable|string|max:255',
            'meeting_role' => 'sometimes|in:chair,secretary,delegate',
        ]);

        $participant = $this->service->update($participant, $validated);

        return $this->successResource(new MeetingParticipantResource($participant), 'Cập nhật thành viên thành công!');
    }

    /**
     * Xóa thành viên
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam participant integer required ID thành viên. Example: 1
     */
    public function destroy(Meeting $meeting, MeetingParticipant $participant)
    {
        $this->service->destroy($participant);

        return $this->success(null, 'Đã xóa thành viên khỏi cuộc họp!');
    }

    /**
     * Điểm danh thành viên
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam participant integer required ID thành viên. Example: 1
     *
     * @bodyParam attendance_status string required Trạng thái: present, absent. Example: present
     * @bodyParam absence_reason string Lý do vắng mặt (bắt buộc nếu absent). Example: Bận công tác
     */
    public function checkin(Request $request, Meeting $meeting, MeetingParticipant $participant)
    {
        $validated = $request->validate([
            'attendance_status' => 'required|in:present,absent',
            'absence_reason' => 'nullable|string|required_if:attendance_status,absent',
        ], [
            'attendance_status.required' => 'Trạng thái điểm danh không được để trống.',
            'absence_reason.required_if' => 'Vui lòng nhập lý do vắng mặt.',
        ]);

        $participant = $this->service->checkin($participant, $validated);

        return $this->successResource(new MeetingParticipantResource($participant), 'Điểm danh thành công!');
    }
}
