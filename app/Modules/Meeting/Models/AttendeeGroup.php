<?php

namespace App\Modules\Meeting\Models;

class AttendeeGroup extends BaseMeetingModel
{
    protected $table = 'm_attendee_groups';

    protected $fillable = ['meeting_type_id', 'name', 'description', 'status', 'organization_id', 'created_by', 'updated_by'];

    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id');
    }

    public function members()
    {
        return $this->hasMany(AttendeeGroupMember::class, 'attendee_group_id');
    }
}
