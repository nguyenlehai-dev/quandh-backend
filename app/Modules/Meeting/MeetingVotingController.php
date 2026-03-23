<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingVoting;
use App\Modules\Meeting\Resources\MeetingVotingResource;
use App\Modules\Meeting\Services\MeetingVotingService;
use Illuminate\Http\Request;

/**
 * @group Meeting - Biểu quyết
 * @header X-Organization-Id ID tổ chức. Example: 1
 *
 * Quản lý biểu quyết: tạo phiên, mở/đóng bỏ phiếu, bỏ phiếu, xem kết quả. Hỗ trợ biểu quyết ẩn danh.
 */
class MeetingVotingController extends Controller
{
    public function __construct(private MeetingVotingService $service) {}

    /**
     * Danh sách phiên biểu quyết
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function index(Meeting $meeting)
    {
        $votings = $this->service->index($meeting);

        return $this->success(MeetingVotingResource::collection($votings));
    }

    /**
     * Tạo phiên biểu quyết mới
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam title string required Tiêu đề nội dung biểu quyết. Example: Phê duyệt ngân sách Q2/2026
     * @bodyParam description string Mô tả chi tiết.
     * @bodyParam meeting_agenda_id integer ID mục nghị sự liên quan. Example: 1
     * @bodyParam type string Loại: public (công khai), anonymous (ẩn danh). Example: public
     */
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_agenda_id' => 'nullable|integer|exists:m_agendas,id',
            'type' => 'sometimes|in:public,anonymous',
        ], [
            'title.required' => 'Tiêu đề biểu quyết không được để trống.',
        ]);

        $voting = $this->service->store($meeting, $validated);

        return $this->successResource(new MeetingVotingResource($voting), 'Đã tạo phiên biểu quyết!', 201);
    }

    /**
     * Cập nhật phiên biểu quyết
     *
     * Chỉ có thể cập nhật khi trạng thái là "pending".
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam voting integer required ID phiên biểu quyết. Example: 1
     *
     * @bodyParam title string Tiêu đề.
     * @bodyParam description string Mô tả.
     * @bodyParam type string Loại: public, anonymous.
     */
    public function update(Request $request, Meeting $meeting, MeetingVoting $voting)
    {
        if ($voting->status !== 'pending') {
            return $this->error('Chỉ có thể cập nhật khi biểu quyết chưa mở.', 422);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'meeting_agenda_id' => 'nullable|integer|exists:m_agendas,id',
            'type' => 'sometimes|in:public,anonymous',
        ]);

        $voting = $this->service->update($voting, $validated);

        return $this->successResource(new MeetingVotingResource($voting), 'Cập nhật biểu quyết thành công!');
    }

    /**
     * Xóa phiên biểu quyết
     *
     * Chỉ có thể xóa khi trạng thái là "pending".
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam voting integer required ID phiên biểu quyết. Example: 1
     */
    public function destroy(Meeting $meeting, MeetingVoting $voting)
    {
        if ($voting->status !== 'pending') {
            return $this->error('Chỉ có thể xóa khi biểu quyết chưa mở.', 422);
        }

        $this->service->destroy($voting);

        return $this->success(null, 'Đã xóa phiên biểu quyết!');
    }

    /**
     * Mở phiên bỏ phiếu
     *
     * Chuyển trạng thái từ "pending" sang "open".
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam voting integer required ID phiên biểu quyết. Example: 1
     */
    public function open(Meeting $meeting, MeetingVoting $voting)
    {
        if ($voting->status !== 'pending') {
            return $this->error('Phiên biểu quyết đã được mở hoặc đã đóng.', 422);
        }

        $voting = $this->service->open($voting);

        return $this->successResource(new MeetingVotingResource($voting), 'Đã mở phiên bỏ phiếu!');
    }

    /**
     * Đóng phiên bỏ phiếu
     *
     * Chuyển trạng thái từ "open" sang "closed".
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam voting integer required ID phiên biểu quyết. Example: 1
     */
    public function close(Meeting $meeting, MeetingVoting $voting)
    {
        if ($voting->status !== 'open') {
            return $this->error('Phiên biểu quyết chưa được mở hoặc đã đóng.', 422);
        }

        $voting = $this->service->close($voting);

        return $this->successResource(new MeetingVotingResource($voting), 'Đã đóng phiên bỏ phiếu!');
    }

    /**
     * Bỏ phiếu
     *
     * Mỗi user chỉ bỏ được 1 phiếu. Nếu bỏ lại sẽ cập nhật phiếu cũ.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam voting integer required ID phiên biểu quyết. Example: 1
     *
     * @bodyParam choice string required Lựa chọn: agree (đồng ý), disagree (không đồng ý), abstain (bỏ phiếu trắng). Example: agree
     */
    public function vote(Request $request, Meeting $meeting, MeetingVoting $voting)
    {
        if ($voting->status !== 'open') {
            return $this->error('Phiên biểu quyết chưa mở hoặc đã đóng.', 422);
        }

        $validated = $request->validate([
            'choice' => 'required|in:agree,disagree,abstain',
        ], [
            'choice.required' => 'Vui lòng chọn lựa chọn bỏ phiếu.',
            'choice.in' => 'Lựa chọn không hợp lệ. Chỉ chấp nhận: agree, disagree, abstain.',
        ]);

        $this->service->vote($voting, $validated['choice']);

        return $this->success(null, 'Đã bỏ phiếu thành công!');
    }

    /**
     * Xem kết quả biểu quyết
     *
     * Nếu biểu quyết ẩn danh, API không trả về thông tin chi tiết từng phiếu.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam voting integer required ID phiên biểu quyết. Example: 1
     *
     * @response 200 {"success": true, "data": {"voting_id": 1, "title": "...", "type": "public", "status": "closed", "summary": {"total": 10, "agree": 7, "disagree": 2, "abstain": 1}, "details": [{"user_id": 1, "user_name": "Admin", "choice": "agree"}]}}
     */
    public function results(Meeting $meeting, MeetingVoting $voting)
    {
        $results = $this->service->results($voting);

        return $this->success($results);
    }
}
