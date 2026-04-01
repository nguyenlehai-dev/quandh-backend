<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingDocument;
use Illuminate\Support\Facades\DB;

class MeetingDocumentService
{
    public function __construct(private MediaService $mediaService) {}

    public function index(Meeting $meeting)
    {
        return $meeting->documents()->with(['media', 'documentType', 'documentField', 'issuingAgency', 'documentSigner'])->get();
    }

    /** Danh sách tất cả tài liệu trên toàn hệ thống. */
    public function globalIndex(array $filters)
    {
        $limit = $filters['limit'] ?? 15;
        $query = MeetingDocument::query()
            ->with(['meeting:id,title', 'media', 'documentType', 'documentField', 'issuingAgency', 'documentSigner'])
            ->whereHas('meeting', fn($q) => $q->userRelated())
            ->orderBy('id', 'desc');

        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }
        if (!empty($filters['document_type_id'])) {
            $query->where('document_type_id', $filters['document_type_id']);
        }
        if (!empty($filters['meeting_type_id'])) {
            $query->whereHas('meeting', fn ($q) => $q->where('meeting_type_id', $filters['meeting_type_id']));
        }

        return $query->paginate($limit);
    }

    /** Xuất dữ liệu tài liệu trên toàn hệ thống. */
    public function export(array $filters)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Meeting\Exports\MeetingDocumentsExport($filters),
            'tai-lieu-cuoc-hop.xlsx'
        );
    }

    /** Tạo tài liệu mới kèm upload file. */
    public function store(Meeting $meeting, array $validated, array $files = []): MeetingDocument
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($meeting, $validated, $files, &$storedFiles) {
                $data = collect($validated)->except(['files'])->all();
                $document = $meeting->documents()->create($data);

                $this->saveDocumentFiles($document, $files, $storedFiles);

                return $document->load(['media', 'documentType', 'documentField', 'issuingAgency', 'documentSigner']);
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

                return $document->load(['media', 'documentType', 'documentField', 'issuingAgency', 'documentSigner']);
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

    /** Upload files cho tài liệu. */
    private function saveDocumentFiles(MeetingDocument $document, array $files, array &$storedFiles): void
    {
        $uploaded = $this->mediaService->uploadMany($document, $files, 'meeting-document-files', [
            'disk' => 'public',
        ]);

        $storedFiles = array_merge($storedFiles, $uploaded);
    }
}
