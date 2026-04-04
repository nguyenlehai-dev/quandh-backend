<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Events\MeetingRealtimeUpdated;
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

        $validated['organization_id'] = $meeting->organization_id;
        $validated['meeting_id'] = $meeting->id;
        $validated['meeting_participant_id'] = $participant->id;
        $validated['status'] = 'pending';

        $speechRequest = MeetingSpeechRequest::create($validated)->load(['participant.user', 'agenda']);

        event(new MeetingRealtimeUpdated(
            meetingId: $meeting->id,
            eventType: 'speech-request.created',
            payload: [
                'speech_request_id' => $speechRequest->id,
                'meeting_agenda_id' => $speechRequest->meeting_agenda_id,
                'status' => $speechRequest->status,
            ],
        ));

        return $speechRequest;
    }

    public function mine(Meeting $meeting)
    {
        return MeetingSpeechRequest::query()
            ->where('meeting_id', $meeting->id)
            ->whereHas('participant', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['participant.user', 'agenda'])
            ->latest()
            ->get();
    }

    /** Cập nhật trạng thái đăng ký phát biểu (duyệt/từ chối). */
    public function updateStatus(MeetingSpeechRequest $speechRequest, string $status): MeetingSpeechRequest
    {
        $payload = ['status' => $status];

        if ($status === 'approved') {
            $payload['approved_by'] = auth()->id();
            $payload['approved_at'] = now();
            $payload['rejected_reason'] = null;
        }

        $speechRequest->update($payload);

        event(new MeetingRealtimeUpdated(
            meetingId: $speechRequest->meeting_id,
            eventType: 'speech-request.status-changed',
            payload: [
                'speech_request_id' => $speechRequest->id,
                'status' => $speechRequest->status,
            ],
        ));

        return $speechRequest->load(['participant.user', 'agenda']);
    }

    /** Xóa đăng ký phát biểu. */
    public function destroy(MeetingSpeechRequest $speechRequest): void
    {
        $speechRequest->delete();
    }
}
