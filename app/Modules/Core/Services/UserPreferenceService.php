<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\User;
use App\Modules\Core\Models\UserPreference;

class UserPreferenceService
{
    /** Lấy ID tổ chức đã lưu (có thể null nếu chưa từng chọn hoặc đã xóa). */
    public function getCurrentOrganizationId(User $user): ?int
    {
        $preference = UserPreference::query()->where('user_id', $user->id)->first();

        if ($preference === null || $preference->current_organization_id === null) {
            return null;
        }

        return (int) $preference->current_organization_id;
    }

    /** Lưu hoặc cập nhật tổ chức làm việc hiện tại (nhớ cho lần đăng nhập sau). */
    public function setCurrentOrganizationId(User $user, int $organizationId): void
    {
        UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['current_organization_id' => $organizationId]
        );
    }

    /** Xóa ngữ cảnh tổ chức đã lưu (khi không còn hợp lệ). */
    public function clearCurrentOrganizationId(User $user): void
    {
        UserPreference::query()->where('user_id', $user->id)->update(['current_organization_id' => null]);
    }
}
