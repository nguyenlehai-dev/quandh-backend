<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Events\MeetingRealtimeUpdated;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingCheckin;
use App\Modules\Meeting\Models\MeetingParticipant;

class MeetingParticipantService
{
    /** Danh sách thành viên của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->participants()->with('user')->get();
    }

    /** Gán thành viên mới cho cuộc họp. */
    public function store(Meeting $meeting, array $validated): MeetingParticipant
    {
        $validated['organization_id'] = $meeting->organization_id;
        $participant = $meeting->participants()->create($validated);

        return $participant->load('user');
    }

    /** Cập nhật thông tin thành viên. */
    public function update(MeetingParticipant $participant, array $validated): MeetingParticipant
    {
        $participant->update($validated);

        return $participant->load('user');
    }

    /** Xóa thành viên khỏi cuộc họp. */
    public function destroy(MeetingParticipant $participant): void
    {
        $participant->delete();
    }

    /** Điểm danh thành viên. */
    public function checkin(MeetingParticipant $participant, array $validated): MeetingParticipant
    {
        $participant->update([
            'attendance_status' => $validated['attendance_status'],
            'checkin_at' => $validated['attendance_status'] === 'present' ? now() : null,
            'absence_reason' => $validated['absence_reason'] ?? null,
        ]);

        if ($validated['attendance_status'] === 'present') {
            MeetingCheckin::create([
                'organization_id' => $participant->organization_id ?: $participant->meeting?->organization_id,
                'meeting_id' => $participant->meeting_id,
                'meeting_participant_id' => $participant->id,
                'type' => 'manual',
                'checked_in_by' => auth()->id(),
                'checked_in_at' => now(),
                'meta' => ['source' => 'admin'],
            ]);
        }

        event(new MeetingRealtimeUpdated(
            meetingId: $participant->meeting_id,
            eventType: 'participant.checkin',
            payload: [
                'participant_id' => $participant->id,
                'user_id' => $participant->user_id,
                'attendance_status' => $participant->attendance_status,
                'checkin_at' => optional($participant->checkin_at)?->toIso8601String(),
            ],
        ));

        return $participant->load('user');
    }
}
