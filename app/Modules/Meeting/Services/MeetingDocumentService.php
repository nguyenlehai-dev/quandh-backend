<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingDocument;
use Illuminate\Support\Facades\DB;

class MeetingDocumentService
{
    public function __construct(private MediaService $mediaService) {}

    private function organizationId(): ?int
    {
        return request()->header('X-Organization-Id') ? (int) request()->header('X-Organization-Id') : null;
    }

    /** Danh sách tài liệu của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->documents()->with(['media', 'agenda'])->get();
    }

    /** Tạo tài liệu mới kèm upload file. */
    public function store(Meeting $meeting, array $validated, array $files = []): MeetingDocument
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($meeting, $validated, $files, &$storedFiles) {
                $data = collect($validated)->except(['files'])->all();
                $data['organization_id'] = $meeting->organization_id;
                $document = $meeting->documents()->create($data);

                $this->saveDocumentFiles($document, $files, $storedFiles);

                return $document->load(['media', 'agenda']);
            });
        } catch (\Throwable $exception) {
            $this->mediaService->cleanupStoredFiles($storedFiles);
            throw $exception;
        }
    }

    /** Cập nhật tài liệu. */
    public function update(MeetingDocument $document, array $validated, array $files = []): MeetingDocument
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($document, $validated, $files, &$storedFiles) {
                $data = collect($validated)->except(['files', 'remove_file_ids'])->all();
                $document->update($data);

                if (! empty($validated['remove_file_ids'])) {
                    $this->mediaService->removeByIds($document, $validated['remove_file_ids'], 'meeting-document-files');
                }

                $this->saveDocumentFiles($document, $files, $storedFiles);

                return $document->load(['media', 'agenda']);
            });
        } catch (\Throwable $exception) {
            $this->mediaService->cleanupStoredFiles($storedFiles);
            throw $exception;
        }
    }

    /** Xóa tài liệu. */
    public function destroy(MeetingDocument $document): void
    {
        $document->delete();
    }

    public function allDocuments(array $filters, int $limit = 10)
    {
        return MeetingDocument::query()
            ->when($this->organizationId(), fn ($q, $orgId) => $q->where('organization_id', $orgId))
            ->with(['meeting', 'agenda', 'media', 'creator', 'editor'])
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where('title', 'like', '%'.$value.'%'))
            ->when($filters['meeting_id'] ?? null, fn ($q, $value) => $q->where('meeting_id', $value))
            ->when($filters['meeting_type_id'] ?? null, function ($q, $value) {
                $q->whereHas('meeting', fn ($meetingQuery) => $meetingQuery->where('meeting_type_id', $value));
            })
            ->when($filters['document_type_id'] ?? null, fn ($q, $value) => $q->where('document_type_id', $value))
            ->when($filters['document_field_id'] ?? null, fn ($q, $value) => $q->where('document_field_id', $value))
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_order'] ?? 'desc')
            ->paginate($limit);
    }

    /** Upload files cho tài liệu. */
    private function saveDocumentFiles(MeetingDocument $document, array $files, array &$storedFiles): void
    {
        $uploaded = $this->mediaService->uploadMany($document, $files, 'meeting-document-files', [
            'disk' => 'public',
        ]);

        $storedFiles = array_merge($storedFiles, $uploaded);
    }
}
