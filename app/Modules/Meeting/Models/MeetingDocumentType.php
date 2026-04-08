<?php

namespace App\Modules\Meeting\Models;

class MeetingDocumentType extends BaseMeetingModel
{
    protected $table = 'm_document_types';

    protected $fillable = ['meeting_type_id', 'name', 'description', 'status', 'organization_id', 'created_by', 'updated_by'];

    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id');
    }
}
