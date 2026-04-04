<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingAgenda;
use App\Modules\Meeting\Resources\MeetingAgendaResource;
use App\Modules\Meeting\Services\MeetingAgendaService;
use Illuminate\Http\Request;

/**
 * @group Meeting - Chương trình nghị sự
 * @header X-Organization-Id ID tổ chức. Example: 1
 *
 * Quản lý chương trình cuộc họp: tạo, cập nhật, xóa, sắp xếp lại thứ tự.
 */
class MeetingAgendaController extends Controller
{
    public function __construct(private MeetingAgendaService $service) {}

    /**
     * Danh sách mục nghị sự
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function index(Meeting $meeting)
    {
        $agendas = $this->service->index($meeting);

        return $this->success(MeetingAgendaResource::collection($agendas));
    }

    /**
     * Tạo mục nghị sự
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam title string required Tiêu đề. Example: Báo cáo tài chính Q1
     * @bodyParam description string Mô tả chi tiết.
     * @bodyParam order_index integer Thứ tự (tự động nếu không truyền). Example: 1
     * @bodyParam duration integer Thời lượng (phút). Example: 30
     */
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:1',
        ], [
            'title.required' => 'Tiêu đề mục nghị sự không được để trống.',
        ]);

        $agenda = $this->service->store($meeting, $validated);

        return $this->successResource(new MeetingAgendaResource($agenda), 'Đã thêm mục nghị sự!', 201);
    }

    /**
     * Cập nhật mục nghị sự
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam agenda integer required ID mục nghị sự. Example: 1
     *
     * @bodyParam title string Tiêu đề.
     * @bodyParam description string Mô tả chi tiết.
     * @bodyParam order_index integer Thứ tự.
     * @bodyParam duration integer Thời lượng (phút).
     */
    public function update(Request $request, Meeting $meeting, MeetingAgenda $agenda)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:1',
        ]);

        $agenda = $this->service->update($agenda, $validated);

        return $this->successResource(new MeetingAgendaResource($agenda), 'Cập nhật mục nghị sự thành công!');
    }

    /**
     * Xóa mục nghị sự
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam agenda integer required ID mục nghị sự. Example: 1
     */
    public function destroy(Meeting $meeting, MeetingAgenda $agenda)
    {
        $this->service->destroy($agenda);

        return $this->success(null, 'Đã xóa mục nghị sự!');
    }

    /**
     * Sắp xếp lại thứ tự mục nghị sự
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam ids array required Danh sách ID theo thứ tự mới. Example: [3, 1, 2]
     */
    public function reorder(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:m_agendas,id',
        ], [
            'ids.required' => 'Danh sách ID không được để trống.',
        ]);

        $this->service->reorder($meeting, $validated['ids']);

        return $this->success(null, 'Đã sắp xếp lại thứ tự!');
    }

    public function setActive(Meeting $meeting, MeetingAgenda $agenda)
    {
        abort_unless((int) $agenda->meeting_id === (int) $meeting->id, 422, 'Agenda không thuộc cuộc họp này.');

        $agenda = $this->service->setActive($meeting, $agenda);

        return $this->successResource(new MeetingAgendaResource($agenda), 'Đã cập nhật agenda đang hoạt động!');
    }
}
