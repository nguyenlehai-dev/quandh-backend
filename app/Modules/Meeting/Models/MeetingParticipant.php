<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $table = 'm_participants';

    protected $fillable = [
        'organization_id',
        'meeting_id',
        'user_id',
        'position',
        'meeting_role',
        'attendance_status',
        'checkin_at',
        'absence_reason',
        'delegated_to_id',
        'is_guest',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'checkin_at' => 'datetime',
        'delegated_to_id' => 'integer',
        'is_guest' => 'boolean',
    ];

    /** Cuộc họp. */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /** Người dùng. */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function delegatedTo()
    {
        return $this->belongsTo(User::class, 'delegated_to_id');
    }

    /** Đăng ký phát biểu của thành viên. */
    public function speechRequests()
    {
        return $this->hasMany(MeetingSpeechRequest::class, 'meeting_participant_id');
    }
}
