<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tuỳ chọn người dùng (1–1 với users): lưu tổ chức làm việc gần nhất để đăng nhập sau nhớ ngữ cảnh.
 */
class UserPreference extends Model
{
    protected $table = 'user_preferences';

    protected $fillable = [
        'user_id',
        'current_organization_id',
    ];

    protected function casts(): array
    {
        return [
            'current_organization_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }
}
