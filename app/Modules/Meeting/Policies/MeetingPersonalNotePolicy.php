<?php

namespace App\Modules\Meeting\Policies;

use App\Modules\Core\Models\User;
use App\Modules\Meeting\Models\MeetingPersonalNote;

/**
 * Policy bảo vệ ghi chú cá nhân.
 * Đảm bảo user chỉ có thể truy cập ghi chú của chính mình.
 */
class MeetingPersonalNotePolicy
{
    /** Xem ghi chú: chỉ chủ sở hữu. */
    public function view(User $user, MeetingPersonalNote $note): bool
    {
        return $user->id === $note->user_id;
    }

    /** Cập nhật ghi chú: chỉ chủ sở hữu. */
    public function update(User $user, MeetingPersonalNote $note): bool
    {
        return $user->id === $note->user_id;
    }

    /** Xóa ghi chú: chỉ chủ sở hữu. */
    public function delete(User $user, MeetingPersonalNote $note): bool
    {
        return $user->id === $note->user_id;
    }
}
