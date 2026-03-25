<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Resources\MeetingCollection;
use App\Modules\Meeting\Resources\MeetingParticipantResource;
use App\Modules\Meeting\Resources\MeetingResource;
use App\Modules\Meeting\Services\MyMeetingService;

/**
 * @group MyMeeting - Lịch họp của tôi (Đại biểu)
 * @header X-Organization-Id ID tổ chức cần làm việc (bắt buộc với endpoint yêu cầu auth). Example: 1
 *
 * API cho phân hệ đại biểu: xem danh sách cuộc họp được mời, chi tiết, thông tin tham gia.
 */
class MyMeetingController extends Controller
{
    public function __construct(private MyMeetingService $myMeetingService) {}

    /**
     * Lịch họp của tôi
     *
     * Lấy danh sách cuộc họp mà đại biểu (user hiện tại) được mời tham gia.
     * Hỗ trợ lọc, phân trang giống endpoint admin.
     *
     * @queryParam search string Từ khóa tìm kiếm (tiêu đề). Example: hop-ban
     * @queryParam status string Lọc theo trạng thái: draft, active, in_progress, completed.
     * @queryParam from_date string Lọc từ ngày (Y-m-d). Example: 2026-01-01
     * @queryParam to_date string Lọc đến ngày (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sắp xếp theo: id, title, start_at, created_at. Example: start_at
     * @queryParam sort_order string Thứ tự: asc, desc. Example: desc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     */
    public function index(FilterRequest $request)
    {
        $meetings = $this->myMeetingService->index(
            auth()->id(),
            $request->all(),
            (int) ($request->limit ?? 10)
        );

        return $this->successCollection(new MeetingCollection($meetings));
    }

    /**
     * Chi tiết cuộc họp (phân hệ đại biểu)
     *
     * Lấy thông tin chi tiết cuộc họp kèm agenda, tài liệu, kết luận, biểu quyết
     * và ghi chú cá nhân của chính user hiện tại. Chỉ cho phép nếu user là participant.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @response 403 {"success": false, "message": "Bạn không có quyền truy cập cuộc họp này."}
     */
    public function show(Meeting $meeting)
    {
        $meeting = $this->myMeetingService->show($meeting, auth()->id());

        return $this->successResource(new MeetingResource($meeting));
    }

    /**
     * Thông tin tham gia của tôi
     *
     * Lấy thông tin participant (vai trò, chức vụ, trạng thái điểm danh) của user
     * trong cuộc họp cụ thể.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @response 200 {"success": true, "data": {"id": 1, "position": "Trưởng phòng", "meeting_role": "participant", "attendance_status": "present"}}
     */
    public function myInfo(Meeting $meeting)
    {
        $participant = $this->myMeetingService->myParticipantInfo($meeting, auth()->id());

        return $this->successResource(new MeetingParticipantResource($participant));
    }
}
