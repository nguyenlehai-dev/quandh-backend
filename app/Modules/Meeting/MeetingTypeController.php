<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\MeetingType;
use App\Modules\Meeting\Resources\MeetingTypeResource;
use App\Modules\Meeting\Services\MeetingTypeService;
use Illuminate\Http\Request;

class MeetingTypeController extends Controller
{
    public function __construct(private MeetingTypeService $service) {}

    public function index(Request $request)
    {
        $items = $this->service->index($request->all());
        return $this->successCollection(MeetingTypeResource::collection($items));
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
}
