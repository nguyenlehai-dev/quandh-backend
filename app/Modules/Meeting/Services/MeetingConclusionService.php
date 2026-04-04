<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingConclusion;

class MeetingConclusionService
{
    private function organizationId(): ?int
    {
        return request()->header('X-Organization-Id') ? (int) request()->header('X-Organization-Id') : null;
    }

    /** Danh sách kết luận của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->conclusions()->with('agenda')->get();
    }

    /** Tạo kết luận mới. */
    public function store(Meeting $meeting, array $validated): MeetingConclusion
    {
        $validated['organization_id'] = $meeting->organization_id;

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

    public function allConclusions(array $filters, int $limit = 10)
    {
        return MeetingConclusion::query()
            ->when($this->organizationId(), fn ($q, $orgId) => $q->where('organization_id', $orgId))
            ->with(['meeting', 'agenda', 'creator', 'editor'])
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where('title', 'like', '%'.$value.'%'))
            ->when($filters['meeting_id'] ?? null, fn ($q, $value) => $q->where('meeting_id', $value))
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc')
            ->paginate($limit);
    }
}
