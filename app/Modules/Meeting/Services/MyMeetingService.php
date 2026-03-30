<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingParticipant;

/**
 * MyMeetingService — Phân hệ Đại biểu
 *
 * Lấy danh sách cuộc họp mà user hiện tại được mời tham gia.
 */
class MyMeetingService
{
    /** Danh sách cuộc họp của tôi (có phân trang + lọc). */
    public function index(int $userId, array $filters, int $limit)
    {
        return Meeting::where(function ($query) use ($userId) {
            $query->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
                ->orWhere('created_by', $userId);
        })
            ->with(['creator'])
            ->withCount(['participants', 'agendas', 'documents', 'conclusions'])
            ->filter($filters)
            ->paginate($limit);
    }

    /** Chi tiết cuộc họp — chỉ cho participant đã được mời. */
    public function show(Meeting $meeting, int $userId): Meeting
    {
        // Xác nhận user là participant
        $this->ensureParticipant($meeting, $userId);

        return $meeting->load([
            'participants.user',
            'agendas',
            'documents.media',
            'conclusions',
            'votings.results',
            'personalNotes' => fn ($q) => $q->where('user_id', $userId),
            'creator',
        ]);
    }

    /** Lấy thông tin participant của user trong cuộc họp. */
    public function myParticipantInfo(Meeting $meeting, int $userId): MeetingParticipant
    {
        return $this->ensureParticipant($meeting, $userId)->load('user');
    }

    /** Kiểm tra user có phải participant hay không. */
    private function ensureParticipant(Meeting $meeting, int $userId): MeetingParticipant
    {
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        if (! $participant) {
            abort(403, 'Bạn không có quyền truy cập cuộc họp này.');
        }

        return $participant;
    }
}
