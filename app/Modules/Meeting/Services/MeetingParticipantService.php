<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingParticipant;
use Illuminate\Support\Facades\DB;

class MeetingParticipantService
{
    public function index(Meeting $meeting)
    {
        return $meeting->participants()->with('user')->get();
    }

    /** Danh sách tất cả thành viên trên toàn hệ thống. */
    public function globalIndex(array $filters)
    {
        $limit = $filters['limit'] ?? 15;
        $query = MeetingParticipant::query()
            ->with(['meeting:id,title', 'user'])
            ->has('meeting')
            ->orderBy('id', 'desc');

        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%");
            })->orWhere('name', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($limit);
    }

    /** Xuất dữ liệu thành viên trên toàn hệ thống. */
    public function export(array $filters)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Modules\Meeting\Exports\MeetingParticipantsExport($filters),
            'nguoi-du-hop.xlsx'
        );
    }

    /** Gán thành viên mới cho cuộc họp. */
    public function store(Meeting $meeting, array $validated): MeetingParticipant
    {
        $participant = $meeting->participants()->create($validated);

        return $participant->load('user');
    }

    /** Cập nhật thông tin thành viên. */
    public function update(MeetingParticipant $participant, array $validated): MeetingParticipant
    {
        $participant->update($validated);

        return $participant->load('user');
    }

    /** Xóa thành viên khỏi cuộc họp. */
    public function destroy(MeetingParticipant $participant): void
    {
        $participant->delete();
    }

    /** Điểm danh thành viên. */
    public function checkin(MeetingParticipant $participant, array $validated): MeetingParticipant
    {
        $participant->update([
            'attendance_status' => $validated['attendance_status'],
            'checkin_at' => $validated['attendance_status'] === 'present' ? now() : null,
            'absence_reason' => $validated['absence_reason'] ?? null,
        ]);

        return $participant->load('user');
    }

    /** Tự báo cáo điểm danh, vắng mặt hoặc ủy quyền. */
    public function selfCheckin(MeetingParticipant $participant, array $validated): MeetingParticipant
    {
        $status = $validated['attendance_status'];
        
        $participant->update([
            'attendance_status' => $status,
            'checkin_at' => $status === 'present' ? now() : null,
            'absence_reason' => $status === 'absent' ? ($validated['absence_reason'] ?? null) : null,
            'delegated_to_id' => $status === 'delegated' ? ($validated['delegated_to_id'] ?? null) : null,
        ]);

        return $participant->load('user', 'delegatedUser');
    }
}
