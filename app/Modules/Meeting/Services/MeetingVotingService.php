<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Events\MeetingRealtimeUpdated;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingVoteResult;
use App\Modules\Meeting\Models\MeetingVoting;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MeetingVotingService
{
    private function organizationId(): ?int
    {
        return request()->header('X-Organization-Id') ? (int) request()->header('X-Organization-Id') : null;
    }

    /** Danh sách phiên biểu quyết của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->votings()->with(['agenda', 'results'])->get();
    }

    /** Tạo phiên biểu quyết mới. */
    public function store(Meeting $meeting, array $validated): MeetingVoting
    {
        $validated['organization_id'] = $meeting->organization_id;

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

    /** Mở phiên bỏ phiếu. */
    public function open(MeetingVoting $voting): MeetingVoting
    {
        $voting->update([
            'status' => 'open',
            'opened_at' => now(),
            'closed_at' => null,
        ]);

        event(new MeetingRealtimeUpdated(
            meetingId: $voting->meeting_id,
            eventType: 'voting.opened',
            payload: [
                'voting_id' => $voting->id,
                'title' => $voting->title,
            ],
        ));

        return $voting->load(['agenda', 'results']);
    }

    /** Đóng phiên bỏ phiếu. */
    public function close(MeetingVoting $voting): MeetingVoting
    {
        $voting->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        event(new MeetingRealtimeUpdated(
            meetingId: $voting->meeting_id,
            eventType: 'voting.closed',
            payload: [
                'voting_id' => $voting->id,
                'status' => $voting->status,
            ],
        ));

        return $voting->load(['agenda', 'results']);
    }

    /** Bỏ phiếu (mỗi user chỉ được bỏ 1 phiếu). */
    public function vote(MeetingVoting $voting, string $choice): MeetingVoteResult
    {
        if ($voting->status !== 'open') {
            throw new HttpException(422, 'Phiên biểu quyết hiện chưa mở.');
        }

        $result = MeetingVoteResult::updateOrCreate(
            [
                'meeting_voting_id' => $voting->id,
                'user_id' => auth()->id(),
            ],
            [
                'organization_id' => $voting->organization_id ?: $voting->meeting?->organization_id,
                'choice' => $choice,
            ]
        );

        event(new MeetingRealtimeUpdated(
            meetingId: $voting->meeting_id,
            eventType: 'voting.result-updated',
            payload: [
                'voting_id' => $voting->id,
                'user_id' => auth()->id(),
                'choice' => $choice,
            ],
        ));

        return $result;
    }

    public function currentVoting(Meeting $meeting): ?MeetingVoting
    {
        return $meeting->votings()
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function allVotings(array $filters, int $limit = 10)
    {
        return MeetingVoting::query()
            ->when($this->organizationId(), fn ($q, $orgId) => $q->where('organization_id', $orgId))
            ->with(['meeting', 'agenda', 'results'])
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where('title', 'like', '%'.$value.'%'))
            ->when($filters['meeting_id'] ?? null, fn ($q, $value) => $q->where('meeting_id', $value))
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc')
            ->paginate($limit);
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
