<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MeetingDocument extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'm_documents';

    protected $fillable = [
        'meeting_id',
        'agenda_id',
        'document_type_id',
        'document_field_id',
        'issuing_agency_id',
        'document_signer_id',
        'title',
        'description',
        'document_number',
        'issued_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = ['issued_at' => 'date'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function agenda()
    {
        return $this->belongsTo(MeetingAgenda::class, 'agenda_id');
    }

    public function documentType()
    {
        return $this->belongsTo(MeetingDocumentType::class, 'document_type_id');
    }

    public function documentField()
    {
        return $this->belongsTo(MeetingDocumentField::class, 'document_field_id');
    }

    public function issuingAgency()
    {
        return $this->belongsTo(MeetingIssuingAgency::class, 'issuing_agency_id');
    }

    public function documentSigner()
    {
        return $this->belongsTo(MeetingDocumentSigner::class, 'document_signer_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('meeting-document-attachments');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
