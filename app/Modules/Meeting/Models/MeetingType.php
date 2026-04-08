<?php

namespace App\Modules\Meeting\Models;

class MeetingType extends BaseMeetingModel
{
    protected $table = 'm_meeting_types';

    protected $fillable = ['name', 'description', 'status', 'organization_id', 'created_by', 'updated_by'];

    public function attendeeGroups()
    {
        return $this->hasMany(AttendeeGroup::class, 'meeting_type_id');
    }
}
