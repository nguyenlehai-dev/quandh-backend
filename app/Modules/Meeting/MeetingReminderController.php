<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingReminder;
use App\Modules\Meeting\Resources\MeetingReminderResource;
use App\Modules\Meeting\Services\MeetingReminderService;
use Illuminate\Http\Request;

class MeetingReminderController extends Controller
{
    public function __construct(private MeetingReminderService $service) {}

    public function index(Meeting $meeting)
    {
        return $this->success(
            MeetingReminderResource::collection($this->service->index($meeting))
        );
    }

    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'channel' => 'required|in:database,email,push',
            'remind_at' => 'required|date',
            'status' => 'nullable|in:pending,sent,failed,cancelled',
            'payload' => 'nullable|array',
        ]);

        $reminder = $this->service->store($meeting, $validated);

        return $this->successResource(new MeetingReminderResource($reminder), 'Đã tạo lịch nhắc họp.', 201);
    }

    public function update(Request $request, Meeting $meeting, MeetingReminder $reminder)
    {
        abort_unless((int) $reminder->meeting_id === (int) $meeting->id, 422, 'Reminder không thuộc cuộc họp này.');

        $validated = $request->validate([
            'channel' => 'sometimes|in:database,email,push',
            'remind_at' => 'sometimes|date',
            'status' => 'sometimes|in:pending,sent,failed,cancelled',
            'payload' => 'nullable|array',
            'sent_at' => 'nullable|date',
        ]);

        $reminder = $this->service->update($reminder, $validated);

        return $this->successResource(new MeetingReminderResource($reminder), 'Đã cập nhật lịch nhắc họp.');
    }

    public function destroy(Meeting $meeting, MeetingReminder $reminder)
    {
        abort_unless((int) $reminder->meeting_id === (int) $meeting->id, 422, 'Reminder không thuộc cuộc họp này.');

        $this->service->destroy($reminder);

        return $this->success(null, 'Đã xóa lịch nhắc họp.');
    }
}
