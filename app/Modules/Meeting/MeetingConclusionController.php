<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingConclusion;
use App\Modules\Meeting\Resources\MeetingConclusionResource;
use App\Modules\Meeting\Services\MeetingConclusionService;
use Illuminate\Http\Request;

/**
 * @group Meeting - Kết luận cuộc họp
 * @header X-Organization-Id ID tổ chức. Example: 1
 *
 * Quản lý kết luận cuộc họp (1 cuộc họp có nhiều kết luận).
 */
class MeetingConclusionController extends Controller
{
    public function __construct(private MeetingConclusionService $service) {}

    /**
     * Danh sách toàn bộ kết luận
     */
    public function globalIndex(Request $request)
    {
        $conclusions = $this->service->globalIndex($request->all());

        return $this->successCollection(MeetingConclusionResource::collection($conclusions));
    }

    public function export(Request $request)
    {
        return $this->service->export($request->all());
    }

    /**
     * Danh sách kết luận
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function index(Meeting $meeting)
    {
        $conclusions = $this->service->index($meeting);

        return $this->success(MeetingConclusionResource::collection($conclusions));
    }

    /**
     * Tạo kết luận mới
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam title string required Tiêu đề kết luận. Example: Phê duyệt ngân sách Q2
     * @bodyParam content string required Nội dung kết luận. Example: Ban giám đốc đồng ý phê duyệt ngân sách 500 triệu cho Q2.
     * @bodyParam meeting_agenda_id integer ID mục nghị sự liên quan. Example: 1
     */
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meeting_agenda_id' => 'nullable|integer|exists:m_agendas,id',
        ], [
            'title.required' => 'Tiêu đề kết luận không được để trống.',
            'content.required' => 'Nội dung kết luận không được để trống.',
        ]);

        $conclusion = $this->service->store($meeting, $validated);

        return $this->successResource(new MeetingConclusionResource($conclusion), 'Đã thêm kết luận!', 201);
    }

    /**
     * Cập nhật kết luận
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam conclusion integer required ID kết luận. Example: 1
     *
     * @bodyParam title string Tiêu đề kết luận.
     * @bodyParam content string Nội dung kết luận.
     * @bodyParam meeting_agenda_id integer ID mục nghị sự liên quan.
     */
    public function update(Request $request, Meeting $meeting, MeetingConclusion $conclusion)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'meeting_agenda_id' => 'nullable|integer|exists:m_agendas,id',
        ]);

        $conclusion = $this->service->update($conclusion, $validated);

        return $this->successResource(new MeetingConclusionResource($conclusion), 'Cập nhật kết luận thành công!');
    }

    /**
     * Xóa kết luận
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam conclusion integer required ID kết luận. Example: 1
     */
    public function destroy(Meeting $meeting, MeetingConclusion $conclusion)
    {
        $this->service->destroy($conclusion);

        return $this->success(null, 'Đã xóa kết luận!');
    }
}
