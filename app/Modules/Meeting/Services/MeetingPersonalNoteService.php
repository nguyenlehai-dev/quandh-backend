<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingPersonalNote;

class MeetingPersonalNoteService
{
    /** Danh sách ghi chú cá nhân của user đang đăng nhập trong cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->personalNotes()
            ->ownedByAuth()
            ->with('document')
            ->get();
    }

    /** Tạo ghi chú cá nhân (tự động gán user_id = auth). */
    public function store(Meeting $meeting, array $validated): MeetingPersonalNote
    {
        $validated['user_id'] = auth()->id();

        return $meeting->personalNotes()->create($validated)->load('document');
    }

    /** Cập nhật ghi chú cá nhân (kiểm tra ownership). */
    public function update(MeetingPersonalNote $note, array $validated): MeetingPersonalNote
    {
        $note->update($validated);

        return $note->load('document');
    }

    /** Xóa ghi chú cá nhân. */
    public function destroy(MeetingPersonalNote $note): void
    {
        $note->delete();
    }
}
