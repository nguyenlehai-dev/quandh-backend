<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendeeGroup extends Model
{
    use \App\Modules\Core\Traits\OrganizationScoped;

    use HasFactory;

    protected $table = 'm_attendee_groups';

    protected $fillable = [
        'name',
        'description',
        'status',
        'meeting_type_id',
    ];

    /** Loại cuộc họp mà nhóm này thuộc về. */
    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id');
    }

    /** Danh sách User trong nhóm (qua bảng pivot). */
    public function members()
    {
        return $this->belongsToMany(User::class, 'm_attendee_group_members', 'attendee_group_id', 'user_id')
            ->withPivot('position')
            ->withTimestamps();
    }
}
