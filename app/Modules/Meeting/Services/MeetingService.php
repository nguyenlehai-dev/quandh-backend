<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Events\MeetingRealtimeUpdated;
use App\Modules\Meeting\Enums\MeetingStatusEnum;
use App\Modules\Meeting\Exports\MeetingsExport;
use App\Modules\Meeting\Imports\MeetingsImport;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingCheckin;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MeetingService
{
    /** Thống kê cuộc họp theo bộ lọc. */
    public function stats(array $filters): array
    {
        $base = $this->query($filters);

        return [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', MeetingStatusEnum::Draft->value)->count(),
            'active' => (clone $base)->where('status', MeetingStatusEnum::Active->value)->count(),
            'in_progress' => (clone $base)->where('status', MeetingStatusEnum::InProgress->value)->count(),
            'completed' => (clone $base)->where('status', MeetingStatusEnum::Completed->value)->count(),
        ];
    }

    /** Danh sách cuộc họp có phân trang, lọc và sắp xếp. */
    public function index(array $filters, int $limit)
    {
        return $this->query($filters)
            ->with(['creator', 'editor', 'meetingType', 'activeAgenda'])
            ->withCount(['participants', 'agendas', 'documents', 'conclusions'])
            ->paginate($limit);
    }

    /** Chi tiết cuộc họp kèm quan hệ đầy đủ. */
    public function show(Meeting $meeting): Meeting
    {
        $this->ensureOrganizationScope($meeting);

        return $meeting->load([
            'meetingType',
            'participants.user',
            'participants.delegatedTo',
            'agendas.presenter',
            'activeAgenda',
            'documents.media',
            'documents.agenda',
            'conclusions',
            'votings.results',
            'creator',
            'editor',
        ]);
    }

    /** Tạo cuộc họp mới. */
    public function store(array $validated): Meeting
    {
        $validated['organization_id'] = $this->organizationId();
        $validated['code'] = $validated['code'] ?? $this->generateMeetingCode();
        $meeting = Meeting::create($validated);

        return $meeting->load(['creator', 'editor', 'meetingType']);
    }

    /** Cập nhật cuộc họp. */
    public function update(Meeting $meeting, array $validated): Meeting
    {
        $this->ensureOrganizationScope($meeting);
        $meeting->update($validated);

        return $meeting->load(['creator', 'editor', 'meetingType', 'activeAgenda']);
    }

    /** Xóa cuộc họp. */
    public function destroy(Meeting $meeting): void
    {
        $this->ensureOrganizationScope($meeting);
        $meeting->delete();
    }

    /** Xóa hàng loạt cuộc họp. */
    public function bulkDestroy(array $ids): void
    {
        Meeting::query()
            ->forOrganization($this->organizationId())
            ->whereIn('id', $ids)
            ->delete();
    }

    /** Cập nhật trạng thái hàng loạt. */
    public function bulkUpdateStatus(array $ids, string $status): void
    {
        Meeting::query()
            ->forOrganization($this->organizationId())
            ->whereIn('id', $ids)
            ->update([
                'status' => $status,
                'checkin_opened_at' => $status === MeetingStatusEnum::Active->value ? now() : null,
            ]);
    }

    /** Đổi trạng thái cuộc họp. */
    public function changeStatus(Meeting $meeting, string $status): Meeting
    {
        $this->ensureOrganizationScope($meeting);

        $payload = ['status' => $status];
        if ($status === MeetingStatusEnum::Active->value) {
            $payload['checkin_opened_at'] = now();
            $payload['qr_token'] = $meeting->qr_token ?: $this->generateQrToken();
        }

        $meeting->update($payload);

        event(new MeetingRealtimeUpdated(
            meetingId: $meeting->id,
            eventType: 'meeting.status-changed',
            payload: [
                'status' => $meeting->fresh()->status,
            ],
        ));

        return $meeting->load(['creator', 'editor', 'meetingType']);
    }

    public function ensureQrToken(Meeting $meeting): Meeting
    {
        $this->ensureOrganizationScope($meeting);

        if (! $meeting->qr_token) {
            $meeting->update(['qr_token' => $this->generateQrToken()]);
        }

        return $meeting->fresh();
    }

    /** Xuất danh sách cuộc họp ra Excel. */
    public function export(array $filters): BinaryFileResponse
    {
        return Excel::download(new MeetingsExport($filters), 'meetings.xlsx');
    }

    /** Nhập cuộc họp từ Excel. */
    public function import($file): void
    {
        Excel::import(new MeetingsImport, $file);
    }

    public function dashboard(array $filters = []): array
    {
        $base = $this->query($filters);
        $upcoming = (clone $base)
            ->whereNotNull('start_at')
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'start_at', 'location']);

        $participantsCount = (clone $base)
            ->withCount('participants')
            ->get()
            ->sum('participants_count');

        $presentCount = MeetingCheckin::query()
            ->where('organization_id', $this->organizationId())
            ->whereIn('meeting_id', (clone $base)->pluck('id'))
            ->distinct('meeting_participant_id')
            ->count('meeting_participant_id');

        return [
            'summary' => [
                'total' => (clone $base)->count(),
                'draft' => (clone $base)->where('status', MeetingStatusEnum::Draft->value)->count(),
                'active' => (clone $base)->where('status', MeetingStatusEnum::Active->value)->count(),
                'in_progress' => (clone $base)->where('status', MeetingStatusEnum::InProgress->value)->count(),
                'completed' => (clone $base)->where('status', MeetingStatusEnum::Completed->value)->count(),
            ],
            'upcoming_meetings' => $upcoming,
            'attendance_ratio' => [
                'participants_total' => $participantsCount,
                'checked_in_total' => $presentCount,
                'percentage' => $participantsCount > 0 ? round(($presentCount / $participantsCount) * 100, 2) : 0,
            ],
            'status_chart' => [
                ['status' => 'draft', 'total' => (clone $base)->where('status', MeetingStatusEnum::Draft->value)->count()],
                ['status' => 'active', 'total' => (clone $base)->where('status', MeetingStatusEnum::Active->value)->count()],
                ['status' => 'in_progress', 'total' => (clone $base)->where('status', MeetingStatusEnum::InProgress->value)->count()],
                ['status' => 'completed', 'total' => (clone $base)->where('status', MeetingStatusEnum::Completed->value)->count()],
            ],
            'monthly_chart' => $this->monthlyFrequency($filters),
        ];
    }

    public function reports(array $filters = []): array
    {
        $base = $this->query($filters);

        return [
            'meetings_by_status' => [
                'draft' => (clone $base)->where('status', MeetingStatusEnum::Draft->value)->count(),
                'active' => (clone $base)->where('status', MeetingStatusEnum::Active->value)->count(),
                'in_progress' => (clone $base)->where('status', MeetingStatusEnum::InProgress->value)->count(),
                'completed' => (clone $base)->where('status', MeetingStatusEnum::Completed->value)->count(),
            ],
            'meetings_by_type' => (clone $base)
                ->selectRaw('meeting_type_id, COUNT(*) as total')
                ->groupBy('meeting_type_id')
                ->get(),
            'participant_summary' => [
                'total' => \App\Modules\Meeting\Models\MeetingParticipant::query()
                    ->where('organization_id', $this->organizationId())
                    ->count(),
                'present' => \App\Modules\Meeting\Models\MeetingParticipant::query()
                    ->where('organization_id', $this->organizationId())
                    ->where('attendance_status', 'present')
                    ->count(),
                'absent' => \App\Modules\Meeting\Models\MeetingParticipant::query()
                    ->where('organization_id', $this->organizationId())
                    ->where('attendance_status', 'absent')
                    ->count(),
            ],
            'monthly_frequency' => $this->monthlyFrequency($filters),
        ];
    }

    public function live(Meeting $meeting): array
    {
        $meeting = $this->show($meeting);

        return [
            'meeting' => $meeting,
            'attendance_summary' => [
                'total' => $meeting->participants->count(),
                'present' => $meeting->participants->where('attendance_status', 'present')->count(),
                'absent' => $meeting->participants->where('attendance_status', 'absent')->count(),
                'pending' => $meeting->participants->where('attendance_status', 'pending')->count(),
            ],
            'active_agenda' => $meeting->activeAgenda,
            'pending_speech_requests' => $meeting->load(['participants.speechRequests'])->participants
                ->flatMap->speechRequests
                ->where('status', 'pending')
                ->values(),
            'open_votings' => $meeting->votings->where('status', 'open')->values(),
        ];
    }

    public function participantMeetings(Authenticatable $user, array $filters, int $limit)
    {
        return $this->query($filters)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->getAuthIdentifier()))
            ->with(['activeAgenda', 'meetingType'])
            ->withCount(['participants', 'documents', 'agendas'])
            ->paginate($limit);
    }

    public function participantShow(Meeting $meeting, Authenticatable $user): Meeting
    {
        $this->ensureParticipantAccess($meeting, $user);

        return $meeting->load([
            'meetingType',
            'activeAgenda',
            'agendas.presenter',
            'documents.media',
            'votings.results',
            'conclusions',
            'participants' => fn ($q) => $q->where('user_id', $user->getAuthIdentifier()),
        ]);
    }

    public function selfCheckin(Meeting $meeting, Authenticatable $user, string $type = 'self'): array
    {
        $this->ensureParticipantAccess($meeting, $user);

        if (! in_array($meeting->status, [MeetingStatusEnum::Active->value, MeetingStatusEnum::InProgress->value], true)) {
            throw new HttpException(422, 'Cuộc họp hiện chưa mở check-in.');
        }

        $participant = $meeting->participants()->where('user_id', $user->getAuthIdentifier())->firstOrFail();
        $participant->update([
            'attendance_status' => 'present',
            'checkin_at' => now(),
        ]);

        MeetingCheckin::create([
            'organization_id' => $meeting->organization_id,
            'meeting_id' => $meeting->id,
            'meeting_participant_id' => $participant->id,
            'type' => $type,
            'checked_in_by' => $user->getAuthIdentifier(),
            'checked_in_at' => now(),
            'meta' => ['source' => $type],
        ]);

        event(new MeetingRealtimeUpdated(
            meetingId: $meeting->id,
            eventType: 'participant.self-checkin',
            payload: [
                'participant_id' => $participant->id,
                'user_id' => $participant->user_id,
                'type' => $type,
                'attendance_status' => $participant->attendance_status,
                'checkin_at' => optional($participant->checkin_at)?->toIso8601String(),
            ],
        ));

        return [
            'meeting_id' => $meeting->id,
            'participant_id' => $participant->id,
            'attendance_status' => $participant->attendance_status,
            'checkin_at' => $participant->checkin_at,
        ];
    }

    public function participantCandidates(Meeting $meeting)
    {
        $this->ensureOrganizationScope($meeting);

        return \App\Modules\Core\Models\User::query()
            ->whereNotIn('id', $meeting->participants()->pluck('user_id'))
            ->select(['id', 'name', 'email', 'user_name'])
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function ensureParticipantAccess(Meeting $meeting, Authenticatable $user): void
    {
        $this->ensureOrganizationScope($meeting);

        $exists = $meeting->participants()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();

        if (! $exists) {
            throw new AuthorizationException('Bạn không thuộc thành phần tham dự của cuộc họp này.');
        }
    }

    private function query(array $filters = [])
    {
        return Meeting::query()
            ->forOrganization($this->organizationId())
            ->filter($filters);
    }

    private function ensureOrganizationScope(Meeting $meeting): void
    {
        $organizationId = $this->organizationId();

        if ($organizationId && (int) $meeting->organization_id !== $organizationId) {
            throw new AuthorizationException('Bạn không có quyền truy cập cuộc họp này.');
        }
    }

    private function organizationId(): ?int
    {
        return request()->header('X-Organization-Id') ? (int) request()->header('X-Organization-Id') : null;
    }

    private function generateMeetingCode(): string
    {
        return 'MTG-'.now()->format('YmdHis');
    }

    private function generateQrToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function monthlyFrequency(array $filters = []): array
    {
        $driver = DB::connection()->getDriverName();
        $monthExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m', start_at)",
            'pgsql' => "to_char(start_at, 'YYYY-MM')",
            default => "DATE_FORMAT(start_at, '%Y-%m')",
        };

        $rows = $this->query($filters)
            ->selectRaw("{$monthExpression} as month_key, COUNT(*) as total")
            ->whereNotNull('start_at')
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        return $rows->map(fn ($row) => [
            'month' => $row->month_key,
            'total' => (int) $row->total,
        ])->all();
    }
}
