<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingAgenda;
use App\Modules\Meeting\Models\MeetingConclusion;
use App\Modules\Meeting\Models\MeetingDocument;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Models\MeetingPersonalNote;
use App\Modules\Meeting\Models\MeetingReminder;
use App\Modules\Meeting\Models\MeetingSpeechRequest;
use App\Modules\Meeting\Models\MeetingVoteResult;
use App\Modules\Meeting\Models\MeetingVoting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MeetingChildService
{
    protected array $models = [
        'participants' => MeetingParticipant::class,
        'agendas' => MeetingAgenda::class,
        'documents' => MeetingDocument::class,
        'conclusions' => MeetingConclusion::class,
        'speech-requests' => MeetingSpeechRequest::class,
        'votings' => MeetingVoting::class,
        'personal-notes' => MeetingPersonalNote::class,
        'reminders' => MeetingReminder::class,
    ];

    public function __construct(private MediaService $mediaService) {}

    public function index(int $meetingId, string $child, int $limit)
    {
        $this->findMeeting($meetingId);

        return $this->modelClass($child)::query()
            ->where('meeting_id', $meetingId)
            ->with($this->relations($child))
            ->paginate($limit);
    }

    public function store(int $meetingId, string $child, array $validated, array $attachments = []): Model
    {
        $this->findMeeting($meetingId);
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($meetingId, $child, $validated, $attachments, &$storedFiles) {
                $data = $this->defaults($child, $validated);
                $data['meeting_id'] = $meetingId;
                $data = $this->withAuditColumns($this->modelClass($child), $data);
                $item = $this->modelClass($child)::query()->create($data);

                $storedFiles = $this->uploadAttachments($child, $item, $attachments);

                return $item->load($this->relations($child));
            });
        } catch (\Throwable $exception) {
            $this->mediaService->cleanupStoredFiles($storedFiles);
            throw $exception;
        }
    }

    public function update(int $meetingId, string $child, int $id, array $validated, array $attachments = []): Model
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($meetingId, $child, $id, $validated, $attachments, &$storedFiles) {
                $item = $this->findChild($meetingId, $child, $id);
                $data = collect($validated)->except(['attachments', 'remove_attachment_ids'])->all();
                $data = $this->withAuditColumns($item::class, $data, false);
                $item->update($data);

                if ($child === 'documents' && ! empty($validated['remove_attachment_ids'])) {
                    $this->mediaService->removeByIds($item, $validated['remove_attachment_ids'], 'meeting-document-attachments');
                }

                $storedFiles = $this->uploadAttachments($child, $item, $attachments);

                return $item->load($this->relations($child));
            });
        } catch (\Throwable $exception) {
            $this->mediaService->cleanupStoredFiles($storedFiles);
            throw $exception;
        }
    }

    public function destroy(int $meetingId, string $child, int $id): void
    {
        $this->findChild($meetingId, $child, $id)->delete();
    }

    public function storeVoteResult(int $meetingId, int $votingId, array $validated): MeetingVoteResult
    {
        $voting = $this->findChild($meetingId, 'votings', $votingId);
        $validated['user_id'] ??= auth()->id();

        return MeetingVoteResult::query()->updateOrCreate(
            ['voting_id' => $voting->id, 'user_id' => $validated['user_id']],
            ['option' => $validated['option'], 'note' => $validated['note'] ?? null]
        )->load('user');
    }

    public function findMeeting(int $id): Meeting
    {
        return Meeting::query()->forCurrentOrganization()->findOrFail($id);
    }

    public function findChild(int $meetingId, string $child, int $id): Model
    {
        $this->findMeeting($meetingId);

        return $this->modelClass($child)::query()->where('meeting_id', $meetingId)->findOrFail($id);
    }

    public function relations(string $child): array
    {
        return match ($child) {
            'participants' => ['user', 'delegatedTo'],
            'agendas' => ['presenter'],
            'documents' => ['media', 'agenda', 'documentType', 'documentField', 'issuingAgency', 'documentSigner'],
            'conclusions' => ['agenda'],
            'speech-requests' => ['user', 'agenda'],
            'votings' => ['agenda', 'results.user'],
            'personal-notes' => ['user', 'document'],
            'reminders' => ['user'],
            default => [],
        };
    }

    protected function modelClass(string $child): string
    {
        abort_unless(isset($this->models[$child]), 404, 'Không tìm thấy chức năng con của Meeting.');

        return $this->models[$child];
    }

    protected function defaults(string $child, array $validated): array
    {
        unset($validated['attachments'], $validated['remove_attachment_ids']);

        if ($child === 'speech-requests') {
            $validated['user_id'] ??= auth()->id();
        }

        if (in_array($child, ['personal-notes', 'reminders'], true)) {
            $validated['user_id'] ??= auth()->id();
        }

        if ($child === 'participants') {
            $validated['role'] ??= 'delegate';
            $validated['status'] ??= 'pending';
        }

        if ($child === 'agendas') {
            $validated['status'] ??= 'pending';
        }

        if ($child === 'documents') {
            $validated['status'] ??= 'draft';
        }

        if ($child === 'votings') {
            $validated['type'] ??= 'public';
            $validated['status'] ??= 'pending';
        }

        if ($child === 'reminders') {
            $validated['status'] ??= 'pending';
        }

        return $validated;
    }

    protected function withAuditColumns(string $modelClass, array $data, bool $creating = true): array
    {
        $model = new $modelClass;
        if ($creating && $model->isFillable('created_by')) {
            $data['created_by'] ??= auth()->id();
        }
        if ($model->isFillable('updated_by')) {
            $data['updated_by'] ??= auth()->id();
        }

        return $data;
    }

    protected function uploadAttachments(string $child, Model $item, array $attachments): array
    {
        if ($child !== 'documents' || empty($attachments)) {
            return [];
        }

        return $this->mediaService->uploadMany($item, $attachments, 'meeting-document-attachments', [
            'disk' => 'public',
        ]);
    }
}
