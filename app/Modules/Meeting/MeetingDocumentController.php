<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingDocument;
use App\Modules\Meeting\Resources\MeetingDocumentResource;
use App\Modules\Meeting\Services\MeetingDocumentService;
use Illuminate\Http\Request;

/**
 * @group Meeting - Tài liệu cuộc họp
 * @header X-Organization-Id ID tổ chức. Example: 1
 *
 * Quản lý tài liệu đính kèm cuộc họp: upload, cập nhật, xóa file.
 */
class MeetingDocumentController extends Controller
{
    public function __construct(private MeetingDocumentService $service) {}

    /**
     * Danh sách toàn bộ tài liệu
     */
    public function globalIndex(Request $request)
    {
        $documents = $this->service->globalIndex($request->all());

        return $this->successCollection(MeetingDocumentResource::collection($documents));
    }

    public function export(Request $request)
    {
        return $this->service->export($request->all());
    }

    /**
     * Danh sách tài liệu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function index(Meeting $meeting)
    {
        $documents = $this->service->index($meeting);

        return $this->success(MeetingDocumentResource::collection($documents));
    }

    /**
     * Tạo tài liệu mới
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam title string required Tên tài liệu. Example: Báo cáo tài chính Q1/2026
     * @bodyParam description string Mô tả.
     * @bodyParam files[] file Danh sách file đính kèm (pdf, doc, docx, xls, xlsx, ppt, pptx, tối đa 20MB mỗi file).
     */
    public function store(Request $request, Meeting $meeting)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'document_type_id' => 'nullable|integer',
                'document_field_id' => 'nullable|integer',
                'issuing_agency_id' => 'nullable|integer',
                'document_signer_id' => 'nullable|integer',
                'files' => 'nullable|array',
                'files.*' => 'file|max:20480',
            ], [
                'title.required' => 'Tên tài liệu không được để trống.',
                'files.*.max' => 'Kích thước mỗi file tối đa 20MB.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed in Document Store: ', $e->errors());
            \Log::error('Request files: ', $request->allFiles());
            \Log::error('Request data: ', $request->all());
            throw $e;
        }

        $files = $request->file('files', []);
        $document = $this->service->store($meeting, $validated, $files);

        return $this->successResource(new MeetingDocumentResource($document), 'Tạo tài liệu thành công!', 201);
    }

    /**
     * Cập nhật tài liệu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam document integer required ID tài liệu. Example: 1
     *
     * @bodyParam title string Tên tài liệu.
     * @bodyParam description string Mô tả.
     * @bodyParam files[] file File mới (append).
     * @bodyParam remove_file_ids array Danh sách ID media cần xóa. Example: [1, 2]
     */
    public function update(Request $request, Meeting $meeting, MeetingDocument $document)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'document_type_id' => 'nullable|integer',
            'document_field_id' => 'nullable|integer',
            'issuing_agency_id' => 'nullable|integer',
            'document_signer_id' => 'nullable|integer',
            'files' => 'nullable|array',
            'files.*' => 'file|max:20480',
            'remove_file_ids' => 'nullable|array',
            'remove_file_ids.*' => 'integer',
        ]);

        $files = $request->file('files', []);
        $document = $this->service->update($document, $validated, $files);

        return $this->successResource(new MeetingDocumentResource($document), 'Cập nhật tài liệu thành công!');
    }

    /**
     * Xóa tài liệu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam document integer required ID tài liệu. Example: 1
     */
    public function destroy(Meeting $meeting, MeetingDocument $document)
    {
        $this->service->destroy($document);

        return $this->success(null, 'Đã xóa tài liệu!');
    }
}
