<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Models\MeetingSpeechRequest;

class MeetingSpeechRequestService
{
    /** Danh sách đăng ký phát biểu của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return MeetingSpeechRequest::whereHas('participant', function ($q) use ($meeting) {
            $q->where('meeting_id', $meeting->id);
        })->with(['participant.user', 'agenda'])->get();
    }

    /** Nộp đăng ký phát biểu. */
    public function store(Meeting $meeting, array $validated): MeetingSpeechRequest
    {
        // Tìm participant của user đang đăng nhập trong cuộc họp
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated['meeting_participant_id'] = $participant->id;
        $validated['status'] = 'pending';

        return MeetingSpeechRequest::create($validated)->load(['participant.user', 'agenda']);
    }

    /** Cập nhật trạng thái đăng ký phát biểu (duyệt/từ chối). */
    public function updateStatus(MeetingSpeechRequest $speechRequest, string $status): MeetingSpeechRequest
    {
        $speechRequest->update(['status' => $status]);

        return $speechRequest->load(['participant.user', 'agenda']);
    }

    /** Xóa đăng ký phát biểu. */
    public function destroy(MeetingSpeechRequest $speechRequest): void
    {
        $speechRequest->delete();
    }
}
