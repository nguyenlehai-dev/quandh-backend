<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingConclusion;

class MeetingConclusionService
{
    public function index(Meeting $meeting)
    {
        return $meeting->conclusions()->with('agenda')->get();
    }

    /** Danh sách tất cả kết luận trên toàn hệ thống (thuộc các cuộc họp có quyền truy cập). */
    public function globalIndex(array $filters)
    {
        $limit = $filters['limit'] ?? 15;
        $query = MeetingConclusion::query()
            ->with(['meeting:id,title', 'agenda:id,title'])
            ->whereHas('meeting', fn ($q) => $q->userRelated())
            ->orderBy('id', 'desc');

        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }
        if (!empty($filters['meeting_type_id'])) {
            $query->whereHas('meeting', fn ($q) => $q->where('meeting_type_id', $filters['meeting_type_id']));
        }

        return $query->paginate($limit);
    }

    /** Xuất dữ liệu kết luận trên toàn hệ thống. */
    public function export(array $filters)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Meeting\Exports\MeetingConclusionsExport($filters),
            'ket-luan-cuoc-hop.xlsx'
        );
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
