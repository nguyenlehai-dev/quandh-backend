<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingConclusion;

class MeetingConclusionService
{
    /** Danh sách kết luận của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->conclusions()->with('agenda')->get();
    }

    /** Tạo kết luận mới. */
    public function store(Meeting $meeting, array $validated): MeetingConclusion
    {
        return $meeting->conclusions()->create($validated)->load('agenda');
    }

    /** Cập nhật kết luận. */
    public function update(MeetingConclusion $conclusion, array $validated): MeetingConclusion
    {
        $conclusion->update($validated);

        return $conclusion->load('agenda');
    }

    /** Xóa kết luận. */
    public function destroy(MeetingConclusion $conclusion): void
    {
        $conclusion->delete();
    }
}
