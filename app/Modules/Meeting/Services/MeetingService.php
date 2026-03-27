<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Meeting\Enums\MeetingStatusEnum;
use App\Modules\Meeting\Events\MeetingAttendanceChecked;
use App\Modules\Meeting\Events\MeetingStatusChanged;
use App\Modules\Meeting\Exports\MeetingsExport;
use App\Modules\Meeting\Imports\MeetingsImport;
use App\Modules\Meeting\Jobs\SendMeetingNotificationsJob;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingVoting;
use App\Modules\Meeting\Models\MeetingDocument;
use App\Modules\Meeting\Models\MeetingParticipant;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MeetingService
{
    public function __construct(private MediaService $mediaService) {}

    /** Thống kê cuộc họp theo bộ lọc. */
    public function stats(array $filters): array
    {
        $base = Meeting::filter($filters);

        $meetingIds = (clone $base)->pluck('id')->toArray();
        $totalVotes = empty($meetingIds) ? 0 : MeetingVoting::whereIn('meeting_id', $meetingIds)->count();
        $totalDocuments = empty($meetingIds) ? 0 : MeetingDocument::whereIn('meeting_id', $meetingIds)->count();

        $meetingsForFrequency = (clone $base)->whereYear('start_at', date('Y'))->get(['id', 'start_at']);
        $chart_frequency = array_fill(0, 12, 0);
        foreach($meetingsForFrequency as $m) {
            if ($m->start_at) {
                $month = (int) $m->start_at->format('n');
                if ($month >= 1 && $month <= 12) {
                    $chart_frequency[$month - 1]++;
                }
            }
        }

        $pending = (clone $base)->where('status', 'draft')->count();
        $activeInProgress = (clone $base)->whereIn('status', [MeetingStatusEnum::Active->value, 'in_progress'])->count();
        $completed = (clone $base)->where('status', 'completed')->count();
        $canceled = (clone $base)->whereNotIn('status', ['draft', MeetingStatusEnum::Active->value, 'in_progress', 'completed'])->count();

        return [
            'total' => (clone $base)->count(),
            'active' => $activeInProgress,
            'inactive' => (clone $base)->where('status', '!=', MeetingStatusEnum::Active->value)->count(),
            'total_votes' => $totalVotes,
            'total_documents' => $totalDocuments,
            'chart_frequency' => $chart_frequency,
            'chart_status_ratio' => [$pending, $activeInProgress, $completed, $canceled],
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
        if (array_key_exists('agendas', $validated) && is_array($validated['agendas'])) {
            $this->syncAgendas($meeting, $validated['agendas']);
        }

        return $meeting->load(['creator', 'editor']);
    }

    /** Cập nhật cuộc họp. */
    public function update(Meeting $meeting, array $validated): Meeting
    {
        $meeting->update($validated);
        if (array_key_exists('agendas', $validated) && is_array($validated['agendas'])) {
            $this->syncAgendas($meeting, $validated['agendas']);
        }

        return $meeting->load(['creator', 'editor']);
    }

    private function syncAgendas(Meeting $meeting, array $agendas): void
    {
        $keepIds = [];
        foreach ($agendas as $index => $agendaData) {
            if (!empty($agendaData['id'])) {
                $agenda = $meeting->agendas()->find($agendaData['id']);
                if ($agenda) {
                    $agenda->update([
                        'title' => $agendaData['title'],
                        'duration' => $agendaData['duration'] ?? 0,
                        'presenter_id' => $agendaData['presenter_id'] ?? null,
                        'order_index' => $index + 1,
                    ]);
                    $keepIds[] = $agenda->id;
                    continue;
                }
            }
            
            $newAgenda = $meeting->agendas()->create([
                'title' => $agendaData['title'],
                'duration' => $agendaData['duration'] ?? 0,
                'presenter_id' => $agendaData['presenter_id'] ?? null,
                'order_index' => $index + 1,
            ]);
            $keepIds[] = $newAgenda->id;
        }

        $meeting->agendas()->whereNotIn('id', $keepIds)->delete();
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

    /** Lấy hoặc sinh QR token cho cuộc họp. */
    public function qrToken(Meeting $meeting): string
    {
        if (! $meeting->qr_token) {
            $meeting->qr_token = $meeting->generateQrToken();
            $meeting->saveQuietly(); // Không trigger events
        }

        return $meeting->qr_token;
    }

    /** Đại biểu điểm danh bằng QR token. */
    public function qrCheckin(Meeting $meeting, string $qrToken, int $userId): MeetingParticipant
    {
        // Verify QR token
        if ($meeting->qr_token !== $qrToken) {
            throw new \InvalidArgumentException('Mã QR không hợp lệ hoặc đã hết hạn.');
        }

        // Kiểm tra cuộc họp đang active/in_progress
        if (! in_array($meeting->status, [MeetingStatusEnum::Active->value, MeetingStatusEnum::InProgress->value])) {
            throw new \InvalidArgumentException('Cuộc họp chưa bắt đầu hoặc đã kết thúc.');
        }

        // Tìm participant
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        if (! $participant) {
            throw new \InvalidArgumentException('Bạn không có trong danh sách đại biểu của cuộc họp này.');
        }

        if ($participant->attendance_status === 'present') {
            throw new \InvalidArgumentException('Bạn đã điểm danh rồi.');
        }

        // Điểm danh
        $participant->update([
            'attendance_status' => 'present',
            'checkin_at' => now(),
        ]);

        // Broadcast real-time cho admin
        event(new MeetingAttendanceChecked($participant->load('user')));

        return $participant;
    }
}
