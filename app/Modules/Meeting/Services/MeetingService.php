<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Meeting\Enums\MeetingStatusEnum;
use App\Modules\Meeting\Events\MeetingStatusChanged;
use App\Modules\Meeting\Exports\MeetingsExport;
use App\Modules\Meeting\Imports\MeetingsImport;
use App\Modules\Meeting\Jobs\SendMeetingNotificationsJob;
use App\Modules\Meeting\Models\Meeting;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MeetingService
{
    public function __construct(private MediaService $mediaService) {}

    /** Thống kê cuộc họp theo bộ lọc. */
    public function stats(array $filters): array
    {
        $base = Meeting::filter($filters);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', MeetingStatusEnum::Active->value)->count(),
            'inactive' => (clone $base)->where('status', '!=', MeetingStatusEnum::Active->value)->count(),
        ];
    }

    /** Danh sách cuộc họp có phân trang, lọc và sắp xếp. */
    public function index(array $filters, int $limit)
    {
        return Meeting::with(['creator', 'editor'])
            ->withCount(['participants', 'agendas', 'documents', 'conclusions'])
            ->filter($filters)
            ->paginate($limit);
    }

    /** Chi tiết cuộc họp kèm quan hệ đầy đủ. */
    public function show(Meeting $meeting): Meeting
    {
        return $meeting->load([
            'participants.user',
            'agendas',
            'documents.media',
            'conclusions',
            'votings.results',
            'creator',
            'editor',
        ]);
    }

    /** Tạo cuộc họp mới. */
    public function store(array $validated): Meeting
    {
        $meeting = Meeting::create($validated);

        return $meeting->load(['creator', 'editor']);
    }

    /** Cập nhật cuộc họp. */
    public function update(Meeting $meeting, array $validated): Meeting
    {
        $meeting->update($validated);

        return $meeting->load(['creator', 'editor']);
    }

    /** Xóa cuộc họp. */
    public function destroy(Meeting $meeting): void
    {
        $meeting->delete();
    }

    /** Xóa hàng loạt cuộc họp. */
    public function bulkDestroy(array $ids): void
    {
        Meeting::destroy($ids);
    }

    /** Cập nhật trạng thái hàng loạt. */
    public function bulkUpdateStatus(array $ids, string $status): void
    {
        Meeting::whereIn('id', $ids)->update(['status' => $status]);
    }

    /** Đổi trạng thái cuộc họp + phát sự kiện + gửi thông báo. */
    public function changeStatus(Meeting $meeting, string $status): Meeting
    {
        $oldStatus = $meeting->status;
        $meeting->update(['status' => $status]);

        // Phát sự kiện broadcast (real-time)
        event(new MeetingStatusChanged($meeting, $oldStatus, $status));

        // Gửi thông báo hàng loạt khi kích hoạt cuộc họp
        if ($status === MeetingStatusEnum::Active->value) {
            SendMeetingNotificationsJob::dispatch($meeting, 'activated');
        }

        // Gửi thông báo tổng hợp khi kết thúc cuộc họp
        if ($status === MeetingStatusEnum::Completed->value) {
            SendMeetingNotificationsJob::dispatch($meeting, 'completed');
        }

        return $meeting->load(['creator', 'editor']);
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
}
