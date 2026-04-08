<?php

namespace App\Modules\Meeting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Requests\StoreMeetingChildRequest;
use App\Modules\Meeting\Requests\StoreVoteResultRequest;
use App\Modules\Meeting\Requests\UpdateMeetingChildRequest;
use App\Modules\Meeting\Resources\MeetingChildCollection;
use App\Modules\Meeting\Resources\MeetingChildResource;
use App\Modules\Meeting\Services\MeetingChildService;
use Illuminate\Http\Request;

class MeetingChildController extends Controller
{
    public function __construct(private MeetingChildService $meetingChildService) {}

    public function index(Request $request, int $meeting)
    {
        $items = $this->meetingChildService->index($meeting, $this->child($request), (int) $request->input('limit', 50));

        return $this->successCollection(new MeetingChildCollection($items));
    }

    public function store(StoreMeetingChildRequest $request, int $meeting)
    {
        $child = $this->child($request);
        $item = $this->meetingChildService->store($meeting, $child, $request->validated(), $request->file('attachments', []));

        return $this->successResource(new MeetingChildResource($item), 'Tạo dữ liệu cuộc họp thành công!', 201);
    }

    public function update(UpdateMeetingChildRequest $request, int $meeting, int $id)
    {
        $child = $this->child($request);
        $item = $this->meetingChildService->update($meeting, $child, $id, $request->validated(), $request->file('attachments', []));

        return $this->successResource(new MeetingChildResource($item), 'Cập nhật dữ liệu cuộc họp thành công!');
    }

    public function destroy(Request $request, int $meeting, int $id)
    {
        $this->meetingChildService->destroy($meeting, $this->child($request), $id);

        return $this->success(null, 'Xóa dữ liệu cuộc họp thành công!');
    }

    public function storeVoteResult(StoreVoteResultRequest $request, int $meeting, int $voting)
    {
        $result = $this->meetingChildService->storeVoteResult($meeting, $voting, $request->validated());

        return $this->successResource(new MeetingChildResource($result), 'Ghi nhận biểu quyết thành công!', 201);
    }

    protected function child(Request $request): string
    {
        return (string) $request->route('child');
    }
}
