<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendeeGroupMember extends Model
{
    protected $table = 'm_attendee_group_members';

    protected $fillable = ['attendee_group_id', 'user_id', 'position'];

    public function group()
    {
        return $this->belongsTo(AttendeeGroup::class, 'attendee_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
