<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FCM Token cho push notification.
 * 1 user có thể có nhiều tokens (nhiều thiết bị).
 */
class UserFcmToken extends Model
{
    protected $table = 'user_fcm_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'device_name',
        'platform',
    ];

    /** User sở hữu token. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
