<?php

namespace App\Modules\Meeting\Models;

class MeetingDocumentField extends BaseMeetingModel
{
    protected $table = 'm_document_fields';

    protected $fillable = ['name', 'description', 'status', 'organization_id', 'created_by', 'updated_by'];
}
