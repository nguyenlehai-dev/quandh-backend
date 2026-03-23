<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingParticipant;
use Illuminate\Support\Facades\DB;

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

        return $participant->load('user');
    }
}
