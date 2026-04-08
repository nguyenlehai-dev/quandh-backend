<?php

namespace App\Modules\Meeting\Models;

class MeetingDocumentSigner extends BaseMeetingModel
{
    protected $table = 'm_document_signers';

    protected $fillable = ['name', 'position', 'description', 'status', 'organization_id', 'created_by', 'updated_by'];
}
