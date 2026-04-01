<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum', 'set.permissions.team']]);

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Kênh broadcast cho module Meeting.
| Xác thực user có quyền truy cập kênh private.
*/

// Kênh riêng cho từng cuộc họp — chỉ thành viên mới được subscribe
Broadcast::channel('meeting.{meetingId}', function ($user, $meetingId) {
    // Admin/superadmin có quyền điều hành cuộc họp được phép subscribe
    if ($user->hasAnyRole(['super-admin', 'admin']) || $user->can('meetings.changeStatus')) {
        return true;
    }

    // Đại biểu chỉ nghe kênh của cuộc họp mình tham dự
    return \App\Modules\Meeting\Models\MeetingParticipant::where('meeting_id', $meetingId)
        ->where('user_id', $user->id)
        ->exists();
});
