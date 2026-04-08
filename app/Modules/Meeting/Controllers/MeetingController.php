<?php

namespace App\Modules\Meeting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Requests\BulkDestroyRequest;
use App\Modules\Meeting\Requests\BulkUpdateMeetingStatusRequest;
use App\Modules\Meeting\Requests\CheckInMeetingRequest;
use App\Modules\Meeting\Requests\ChangeMeetingStatusRequest;
use App\Modules\Meeting\Requests\ImportMeetingRequest;
use App\Modules\Meeting\Requests\StoreMeetingRequest;
use App\Modules\Meeting\Requests\UpdateMeetingRequest;
use App\Modules\Meeting\Resources\MeetingChildResource;
use App\Modules\Meeting\Resources\MeetingCollection;
use App\Modules\Meeting\Resources\MeetingResource;
use App\Modules\Meeting\Services\MeetingService;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function __construct(private MeetingService $meetingService) {}

    public function stats(Request $request)
    {
        return $this->success($this->meetingService->stats($request->all()));
    }

    public function index(Request $request)
    {
        $items = $this->meetingService->index($request->all(), (int) $request->input('limit', 10));

        return $this->successCollection(new MeetingCollection($items));
    }

    public function myCalendar(Request $request)
    {
        return $this->success(MeetingResource::collection($this->meetingService->myCalendar($request->all())));
    }

    public function show(int $meeting)
    {
        return $this->successResource(new MeetingResource($this->meetingService->show($meeting)));
    }

    public function store(StoreMeetingRequest $request)
    {
        $meeting = $this->meetingService->store($request->validated());

        return $this->successResource(new MeetingResource($meeting), 'Tạo cuộc họp thành công!', 201);
    }

    public function update(UpdateMeetingRequest $request, int $meeting)
    {
        $meeting = $this->meetingService->update($meeting, $request->validated());

        return $this->successResource(new MeetingResource($meeting), 'Cập nhật cuộc họp thành công!');
    }

    public function destroy(int $meeting)
    {
        $this->meetingService->destroy($meeting);

        return $this->success(null, 'Xóa cuộc họp thành công!');
    }

    public function bulkDestroy(BulkDestroyRequest $request)
    {
        $this->meetingService->bulkDestroy($request->validated('ids'));

        return $this->success(null, 'Xóa hàng loạt thành công!');
    }

    public function bulkUpdateStatus(BulkUpdateMeetingStatusRequest $request)
    {
        $this->meetingService->bulkUpdateStatus($request->validated('ids'), $request->validated('status'));

        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    public function changeStatus(ChangeMeetingStatusRequest $request, int $meeting)
    {
        $meeting = $this->meetingService->changeStatus($meeting, $request->validated('status'));

        return $this->successResource(new MeetingResource($meeting), 'Đổi trạng thái cuộc họp thành công!');
    }

    public function qrToken(int $meeting)
    {
        return $this->success($this->meetingService->qrToken($meeting));
    }

    public function regenerateQrToken(int $meeting)
    {
        $meeting = $this->meetingService->regenerateQrToken($meeting);

        return $this->successResource(new MeetingResource($meeting), 'Tạo lại mã QR cuộc họp thành công!');
    }

    public function checkIn(CheckInMeetingRequest $request)
    {
        $participant = $this->meetingService->checkInByQrToken(
            $request->validated('qr_token'),
            $request->validated('user_id')
        );

        return $this->successResource(new MeetingChildResource($participant), 'Check-in cuộc họp thành công!');
    }

    public function export(Request $request)
    {
        return $this->meetingService->export($request->all());
    }

    public function import(ImportMeetingRequest $request)
    {
        $this->meetingService->import($request->file('file'));

        return $this->success(null, 'Import cuộc họp thành công.');
    }
}
