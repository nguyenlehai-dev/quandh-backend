<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Exports\MeetingsExport;
use App\Modules\Meeting\Imports\MeetingsImport;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingParticipant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Str;

class MeetingService
{
    public function stats(array $filters): array
    {
        $query = Meeting::query()->filter($filters);

        return [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    public function index(array $filters, int $limit)
    {
        return Meeting::query()
            ->with(['meetingType', 'creator', 'editor'])
            ->withCount(['participants', 'agendas', 'documents', 'conclusions', 'votings'])
            ->filter($filters)
            ->paginate($limit);
    }

    public function myCalendar(array $filters)
    {
        return Meeting::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['meetingType'])
            ->filter($filters)
            ->get();
    }

    public function show(int $id): Meeting
    {
        return $this->find($id)->load($this->overviewRelations());
    }

    public function store(array $validated): Meeting
    {
        $validated['status'] ??= 'draft';
        $validated['qr_token'] ??= (string) Str::uuid();
        $validated['code'] ??= 'MTG-'.now()->format('YmdHis');

        return Meeting::query()->create($validated)->load($this->overviewRelations());
    }

    public function update(int $id, array $validated): Meeting
    {
        $meeting = $this->find($id);
        $meeting->update($validated);

        return $meeting->load($this->overviewRelations());
    }

    public function destroy(int $id): void
    {
        $this->find($id)->delete();
    }

    public function bulkDestroy(array $ids): void
    {
        Meeting::query()->forCurrentOrganization()->whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): void
    {
        Meeting::query()->forCurrentOrganization()->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function changeStatus(int $id, string $status): Meeting
    {
        $meeting = $this->find($id);
        $meeting->update(['status' => $status]);

        return $meeting->fresh()->load($this->overviewRelations());
    }

    public function qrToken(int $id): array
    {
        return ['qr_token' => $this->find($id)->qr_token];
    }

    public function regenerateQrToken(int $id): Meeting
    {
        $meeting = $this->find($id);
        $meeting->update(['qr_token' => (string) Str::uuid()]);

        return $meeting->fresh()->load($this->overviewRelations());
    }

    public function checkInByQrToken(string $qrToken, ?int $userId = null): MeetingParticipant
    {
        $meeting = Meeting::query()
            ->forCurrentOrganization()
            ->where('qr_token', $qrToken)
            ->firstOrFail();

        $participantUserId = $userId ?: auth()->id();

        if (! $participantUserId) {
            throw new ModelNotFoundException('Không xác định được người check-in.');
        }

        return MeetingParticipant::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $participantUserId],
            ['status' => 'present', 'checkin_at' => now()]
        )->load(['meeting.meetingType', 'user', 'delegatedTo']);
    }

    public function export(array $filters): BinaryFileResponse
    {
        return Excel::download(new MeetingsExport($filters), 'meetings.xlsx');
    }

    public function import($file): void
    {
        Excel::import(new MeetingsImport($this->resolveCurrentOrganizationId()), $file);
    }

    public function find(int $id): Meeting
    {
        return Meeting::query()->forCurrentOrganization()->findOrFail($id);
    }

    public function overviewRelations(): array
    {
        return [
            'meetingType',
            'activeAgenda',
            'participants.user',
            'participants.delegatedTo',
            'agendas.presenter',
            'documents.media',
            'documents.agenda',
            'documents.documentType',
            'documents.documentField',
            'documents.issuingAgency',
            'documents.documentSigner',
            'conclusions.agenda',
            'speechRequests.user',
            'speechRequests.agenda',
            'votings.agenda',
            'votings.results.user',
            'personalNotes.user',
            'personalNotes.document',
            'reminders.user',
            'creator',
            'editor',
        ];
    }

    protected function resolveCurrentOrganizationId(): int
    {
        $organizationId = function_exists('getPermissionsTeamId') ? getPermissionsTeamId() : null;

        if (! is_numeric($organizationId) || (int) $organizationId <= 0) {
            throw new ModelNotFoundException('Không xác định được tổ chức làm việc hiện tại.');
        }

        return (int) $organizationId;
    }
}
