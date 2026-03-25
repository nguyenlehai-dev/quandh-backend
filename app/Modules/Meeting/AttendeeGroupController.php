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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
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
        ]);
        $item = $this->service->update($attendee_group, $validated);
        return $this->successResource(new AttendeeGroupResource($item), 'Cập nhật nhóm người dự họp thành công!');
    }

    public function destroy(AttendeeGroup $attendee_group)
    {
        $this->service->destroy($attendee_group);
        return $this->success(null, 'Xóa nhóm người dự họp thành công!');
    }
}
