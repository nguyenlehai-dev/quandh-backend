<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingDocument;
use Illuminate\Support\Facades\DB;

class MeetingDocumentService
{
    public function __construct(private MediaService $mediaService) {}

    /** Danh sách tài liệu của cuộc họp. */
    public function index(Meeting $meeting)
    {
        return $meeting->documents()->with('media')->get();
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

                return $document->load('media');
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

                return $document->load('media');
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
