<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Events\MeetingVotingStatusChanged;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingVoteResult;
use App\Modules\Meeting\Models\MeetingVoting;

class MeetingVotingService
{
    /** Danh sách phiên biểu quyết của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->votings()->with(['agenda', 'results'])->get();
    }

    /** Danh sách tất cả biểu quyết trên toàn hệ thống (của các cuộc họp user có quyền, hoặc thuộc tổ chức). */
    public function globalIndex(array $filters)
    {
        $limit = $filters['limit'] ?? 15;
        $query = MeetingVoting::query()
            ->with(['meeting:id,title', 'agenda:id,title', 'results'])
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

    /** Xuất dữ liệu biểu quyết. */
    public function export(array $filters)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Meeting\Exports\MeetingVotingsExport($filters),
            'bieu-quyet.xlsx'
        );
    }

    /** Tạo phiên biểu quyết mới. */
    public function store(Meeting $meeting, array $validated): MeetingVoting
    {
        return $meeting->votings()->create($validated)->load('agenda');
    }

    /** Cập nhật phiên biểu quyết (chỉ khi chưa mở). */
    public function update(MeetingVoting $voting, array $validated): MeetingVoting
    {
        $voting->update($validated);

        return $voting->load('agenda');
    }

    /** Xóa phiên biểu quyết. */
    public function destroy(MeetingVoting $voting): void
    {
        $voting->delete();
    }

    /** Mở phiên bỏ phiếu + broadcast. */
    public function open(MeetingVoting $voting): MeetingVoting
    {
        $voting->update(['status' => 'open']);

        event(new MeetingVotingStatusChanged($voting->meeting, $voting, 'open'));

        return $voting->load(['agenda', 'results']);
    }

    /** Đóng phiên bỏ phiếu + broadcast. */
    public function close(MeetingVoting $voting): MeetingVoting
    {
        $voting->update(['status' => 'closed']);

        event(new MeetingVotingStatusChanged($voting->meeting, $voting, 'closed'));

        return $voting->load(['agenda', 'results']);
    }

    /** Bỏ phiếu (mỗi user chỉ được bỏ 1 phiếu). */
    public function vote(MeetingVoting $voting, string $choice): MeetingVoteResult
    {
        return MeetingVoteResult::updateOrCreate(
            [
                'meeting_voting_id' => $voting->id,
                'user_id' => auth()->id(),
            ],
            [
                'choice' => $choice,
            ]
        );
    }

    /** Lấy kết quả biểu quyết (ẩn user_id nếu anonymous). */
    public function results(MeetingVoting $voting): array
    {
        $results = $voting->results;

        $summary = [
            'total' => $results->count(),
            'agree' => $results->where('choice', 'agree')->count(),
            'disagree' => $results->where('choice', 'disagree')->count(),
            'abstain' => $results->where('choice', 'abstain')->count(),
        ];

        // Nếu biểu quyết công khai, trả về chi tiết từng phiếu
        $details = [];
        if (! $voting->isAnonymous()) {
            $details = $results->load('user')->map(fn ($result) => [
                'user_id' => $result->user_id,
                'user_name' => $result->user->name ?? 'N/A',
                'choice' => $result->choice,
            ])->values()->all();
        }

        return [
            'voting_id' => $voting->id,
            'title' => $voting->title,
            'type' => $voting->type,
            'status' => $voting->status,
            'summary' => $summary,
            'details' => $details,
        ];
    }
}

