<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Kênh broadcast cho module Meeting.
| Xác thực user có quyền truy cập kênh private.
*/

// Kênh riêng cho từng cuộc họp — chỉ thành viên mới được subscribe
Broadcast::channel('meeting.{meetingId}', function ($user, $meetingId) {
    return \App\Modules\Meeting\Models\MeetingParticipant::where('meeting_id', $meetingId)
        ->where('user_id', $user->id)
        ->exists();
});
