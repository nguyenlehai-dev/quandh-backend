<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingPersonalNote;
use App\Modules\Meeting\Resources\MeetingPersonalNoteResource;
use App\Modules\Meeting\Services\MeetingPersonalNoteService;
use Illuminate\Http\Request;

/**
 * @group Meeting - Ghi chú cá nhân
 * @header X-Organization-Id ID tổ chức. Example: 1
 *
 * Quản lý ghi chú cá nhân của đại biểu. Mỗi người chỉ xem được ghi chú của mình (dữ liệu cô lập).
 */
class MeetingPersonalNoteController extends Controller
{
    public function __construct(private MeetingPersonalNoteService $service) {}

    /**
     * Danh sách ghi chú cá nhân
     *
     * Chỉ trả về ghi chú của user đang đăng nhập.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function index(Meeting $meeting)
    {
        $notes = $this->service->index($meeting);

        return $this->success(MeetingPersonalNoteResource::collection($notes));
    }

    /**
     * Tạo ghi chú cá nhân
     *
     * Tự động gán user_id = user đang đăng nhập.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam content string required Nội dung ghi chú. Example: Cần hỏi lại về ngân sách Q2.
     * @bodyParam meeting_document_id integer ID tài liệu liên quan (nếu ghi chú trên tài liệu). Example: 1
     */
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'meeting_document_id' => 'nullable|integer|exists:m_documents,id',
        ], [
            'content.required' => 'Nội dung ghi chú không được để trống.',
        ]);

        $note = $this->service->store($meeting, $validated);

        return $this->successResource(new MeetingPersonalNoteResource($note), 'Đã lưu ghi chú!', 201);
    }

    /**
     * Cập nhật ghi chú cá nhân
     *
     * Chỉ chủ sở hữu mới được cập nhật.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam note integer required ID ghi chú. Example: 1
     *
     * @bodyParam content string Nội dung ghi chú mới.
     * @bodyParam meeting_document_id integer ID tài liệu liên quan.
     */
    public function update(Request $request, Meeting $meeting, MeetingPersonalNote $note)
    {
        // Kiểm tra quyền sở hữu
        if ($note->user_id !== auth()->id()) {
            return $this->forbidden('Bạn không có quyền cập nhật ghi chú này.');
        }

        $validated = $request->validate([
            'content' => 'sometimes|string',
            'meeting_document_id' => 'nullable|integer|exists:m_documents,id',
        ]);

        $note = $this->service->update($note, $validated);

        return $this->successResource(new MeetingPersonalNoteResource($note), 'Cập nhật ghi chú thành công!');
    }

    /**
     * Xóa ghi chú cá nhân
     *
     * Chỉ chủ sở hữu mới được xóa.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam note integer required ID ghi chú. Example: 1
     */
    public function destroy(Meeting $meeting, MeetingPersonalNote $note)
    {
        // Kiểm tra quyền sở hữu
        if ($note->user_id !== auth()->id()) {
            return $this->forbidden('Bạn không có quyền xóa ghi chú này.');
        }

        $this->service->destroy($note);

        return $this->success(null, 'Đã xóa ghi chú!');
    }
}
