<?php

namespace App\Modules\Meeting\Models;

class MeetingIssuingAgency extends BaseMeetingModel
{
    protected $table = 'm_issuing_agencies';

    protected $fillable = ['name', 'description', 'status', 'organization_id', 'created_by', 'updated_by'];
}
