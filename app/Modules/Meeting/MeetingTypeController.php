<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\StatusEnum;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Meeting\Models\MeetingType;
use App\Modules\Meeting\Resources\MeetingTypeResource;
use App\Modules\Meeting\Services\MeetingTypeService;
use Illuminate\Http\Request;

class MeetingTypeController extends Controller
{
    public function __construct(private MeetingTypeService $service) {}

    public function index(FilterRequest $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', StatusEnum::rule()],
            'sort_by' => 'nullable|in:id,name,status,created_at,updated_at',
        ], [
            'status.in' => 'Trạng thái không hợp lệ. Chỉ chấp nhận active, inactive.',
            'sort_by.in' => 'Trường sắp xếp không hợp lệ.',
        ]);

        $items = $this->service->index(array_merge($request->all(), $validated));

        return $this->successCollection(MeetingTypeResource::collection($items));
    }

    public function export(FilterRequest $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', StatusEnum::rule()],
            'sort_by' => 'nullable|in:id,name,status,created_at,updated_at',
        ], [
            'status.in' => 'Trạng thái không hợp lệ. Chỉ chấp nhận active, inactive.',
            'sort_by.in' => 'Trường sắp xếp không hợp lệ.',
        ]);

        return $this->service->export(array_merge($request->all(), $validated));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);
        $item = $this->service->store($validated);
        return $this->successResource(new MeetingTypeResource($item), 'Tạo loại cuộc họp thành công!', 201);
    }

    public function update(Request $request, MeetingType $meeting_type)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);
        $item = $this->service->update($meeting_type, $validated);
        return $this->successResource(new MeetingTypeResource($item), 'Cập nhật loại cuộc họp thành công!');
    }

    public function destroy(MeetingType $meeting_type)
    {
        $this->service->destroy($meeting_type);
        return $this->success(null, 'Xóa loại cuộc họp thành công!');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:m_meeting_types,id',
        ]);
        
        $this->service->bulkDestroy($validated['ids']);
        return $this->success(null, 'Xóa hàng loạt thành công!');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'integer|exists:m_meeting_types,id',
            'status' => 'required|in:active,inactive',
        ]);
        
        $this->service->bulkUpdate($validated['ids'], ['status' => $validated['status']]);
        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    public function changeStatus(Request $request, MeetingType $meeting_type)
    {
        $validated = $request->validate([
            'status' => ['required', StatusEnum::rule()],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ. Chỉ chấp nhận active, inactive.',
        ]);

        $item = $this->service->changeStatus($meeting_type, $validated['status']);

        return $this->successResource(new MeetingTypeResource($item), 'Cập nhật trạng thái loại cuộc họp thành công!');
    }
}
