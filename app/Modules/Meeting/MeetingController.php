<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Requests\BulkDestroyMeetingRequest;
use App\Modules\Meeting\Requests\BulkUpdateStatusMeetingRequest;
use App\Modules\Meeting\Requests\ChangeStatusMeetingRequest;
use App\Modules\Meeting\Requests\ImportMeetingRequest;
use App\Modules\Meeting\Requests\StoreMeetingRequest;
use App\Modules\Meeting\Requests\UpdateMeetingRequest;
use App\Modules\Meeting\Resources\MeetingCollection;
use App\Modules\Meeting\Resources\MeetingResource;
use App\Modules\Meeting\Services\MeetingService;

/**
 * @group Meeting - Cuộc họp
 * @header X-Organization-Id ID tổ chức cần làm việc (bắt buộc với endpoint yêu cầu auth). Example: 1
 *
 * Quản lý cuộc họp: danh sách, chi tiết, tạo, cập nhật, xóa, thao tác hàng loạt, xuất/nhập Excel.
 */
class MeetingController extends Controller
{
    public function __construct(private MeetingService $meetingService) {}

    /**
     * Thống kê cuộc họp
     *
     * Tổng số, đang kích hoạt (active), không kích hoạt (draft, in_progress, completed). Áp dụng cùng bộ lọc với index.
     *
     * @queryParam search string Từ khóa tìm kiếm (tiêu đề). Example: hop-ban
     * @queryParam status string Lọc theo trạng thái: draft, active, in_progress, completed.
     * @queryParam from_date string Lọc từ ngày tạo (Y-m-d). Example: 2026-01-01
     * @queryParam to_date string Lọc đến ngày tạo (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sắp xếp theo: id, title, start_at, created_at. Example: created_at
     * @queryParam sort_order string Thứ tự: asc, desc. Example: desc
     *
     * @response 200 {"success": true, "data": {"total": 10, "active": 5, "inactive": 5}}
     */
    public function stats(FilterRequest $request)
    {
        return $this->success($this->meetingService->stats($request->all()));
    }

    /**
     * Danh sách cuộc họp
     *
     * Lấy danh sách có phân trang, lọc và sắp xếp.
     *
     * @queryParam search string Từ khóa tìm kiếm (tiêu đề). Example: hop-ban
     * @queryParam status string Lọc theo trạng thái: draft, active, in_progress, completed.
     * @queryParam from_date string Lọc từ ngày tạo (Y-m-d). Example: 2026-01-01
     * @queryParam to_date string Lọc đến ngày tạo (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sắp xếp theo: id, title, start_at, created_at. Example: created_at
     * @queryParam sort_order string Thứ tự: asc, desc. Example: desc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     */
    public function index(FilterRequest $request)
    {
        $meetings = $this->meetingService->index($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new MeetingCollection($meetings));
    }

    /**
     * Chi tiết cuộc họp
     *
     * Lấy chi tiết cuộc họp kèm danh sách thành viên, chương trình, tài liệu, kết luận, biểu quyết.
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function show(Meeting $meeting)
    {
        $meeting = $this->meetingService->show($meeting);

        return $this->successResource(new MeetingResource($meeting));
    }

    /**
     * Tạo cuộc họp mới
     *
     * @bodyParam title string required Tiêu đề cuộc họp. Example: Họp ban giám đốc Q1/2026
     * @bodyParam description string Mô tả chi tiết. Example: Họp tổng kết quý 1 năm 2026.
     * @bodyParam location string Địa điểm. Example: Phòng họp A - Tầng 3
     * @bodyParam start_at string Thời gian bắt đầu (Y-m-d H:i:s). Example: 2026-04-01 08:00:00
     * @bodyParam end_at string Thời gian kết thúc (Y-m-d H:i:s). Example: 2026-04-01 11:00:00
     * @bodyParam status string required Trạng thái: draft, active, in_progress, completed. Example: draft
     */
    public function store(StoreMeetingRequest $request)
    {
        $meeting = $this->meetingService->store($request->validated());

        return $this->successResource(new MeetingResource($meeting), 'Cuộc họp đã được tạo thành công!', 201);
    }

    /**
     * Cập nhật cuộc họp
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam title string Tiêu đề cuộc họp.
     * @bodyParam description string Mô tả chi tiết.
     * @bodyParam location string Địa điểm.
     * @bodyParam start_at string Thời gian bắt đầu (Y-m-d H:i:s).
     * @bodyParam end_at string Thời gian kết thúc (Y-m-d H:i:s).
     * @bodyParam status string Trạng thái: draft, active, in_progress, completed.
     */
    public function update(UpdateMeetingRequest $request, Meeting $meeting)
    {
        $meeting = $this->meetingService->update($meeting, $request->validated());

        return $this->successResource(new MeetingResource($meeting), 'Cuộc họp đã được cập nhật!');
    }

    /**
     * Xóa cuộc họp
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @response 200 {"success": true, "message": "Cuộc họp đã được xóa thành công!"}
     */
    public function destroy(Meeting $meeting)
    {
        $this->meetingService->destroy($meeting);

        return $this->success(null, 'Cuộc họp đã được xóa thành công!');
    }

    /**
     * Xóa hàng loạt cuộc họp
     *
     * @bodyParam ids array required Danh sách ID. Example: [1, 2, 3]
     *
     * @response 200 {"success": true, "message": "Đã xóa thành công các cuộc họp được chọn!"}
     */
    public function bulkDestroy(BulkDestroyMeetingRequest $request)
    {
        $this->meetingService->bulkDestroy($request->ids);

        return $this->success(null, 'Đã xóa thành công các cuộc họp được chọn!');
    }

    /**
     * Cập nhật trạng thái hàng loạt cuộc họp
     *
     * @bodyParam ids array required Danh sách ID. Example: [1, 2, 3]
     * @bodyParam status string required Trạng thái: draft, active, in_progress, completed. Example: active
     *
     * @response 200 {"success": true, "message": "Cập nhật trạng thái thành công các cuộc họp được chọn!"}
     */
    public function bulkUpdateStatus(BulkUpdateStatusMeetingRequest $request)
    {
        $this->meetingService->bulkUpdateStatus($request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái thành công các cuộc họp được chọn!');
    }

    /**
     * Thay đổi trạng thái cuộc họp
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam status string required Trạng thái mới: draft, active, in_progress, completed. Example: active
     */
    public function changeStatus(ChangeStatusMeetingRequest $request, Meeting $meeting)
    {
        $meeting = $this->meetingService->changeStatus($meeting, $request->status);

        return $this->successResource(new MeetingResource($meeting), 'Cập nhật trạng thái thành công!');
    }

    /**
     * Xuất danh sách cuộc họp
     *
     * Áp dụng cùng bộ lọc với index. Xuất ra các trường: id, title, description, location, start_at, end_at, status, participants_count, agendas_count, conclusions_count, created_by, updated_by, created_at, updated_at.
     *
     * @queryParam search string Từ khóa tìm kiếm (tiêu đề).
     * @queryParam status string Lọc theo trạng thái: draft, active, in_progress, completed.
     * @queryParam sort_by string Sắp xếp theo: id, title, start_at, created_at.
     * @queryParam sort_order string Thứ tự: asc, desc.
     */
    public function export(FilterRequest $request)
    {
        return $this->meetingService->export($request->all());
    }

    /**
     * Nhập danh sách cuộc họp
     *
     * Cột bắt buộc: title. Cột không bắt buộc: description, location, status (mặc định "draft").
     *
     * @bodyParam file file required File Excel (xlsx, xls, csv). Cột theo chuẩn export.
     *
     * @response 200 {"success": true, "message": "Import cuộc họp thành công."}
     */
    public function import(ImportMeetingRequest $request)
    {
        $this->meetingService->import($request->file('file'));

        return $this->success(null, 'Import cuộc họp thành công.');
    }
}
