<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendeeGroupMember extends Model
{
    use HasFactory;

    protected $table = 'm_attendee_group_members';

    protected $fillable = [
        'attendee_group_id',
        'user_id',
        'position',
    ];

    public function attendeeGroup()
    {
        return $this->belongsTo(AttendeeGroup::class, 'attendee_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
