<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use App\Modules\Document\Models\DocumentField;
use App\Modules\Document\Models\DocumentSigner;
use App\Modules\Document\Models\DocumentType;
use App\Modules\Document\Models\IssuingAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MeetingDocument extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'm_documents';

    protected $fillable = [
        'meeting_id',
        'document_type_id',
        'document_field_id',
        'issuing_agency_id',
        'document_signer_id',
        'title',
        'description',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(fn ($doc) => $doc->created_by = $doc->updated_by = auth()->id());
        static::updating(fn ($doc) => $doc->updated_by = auth()->id());
    }

    /** Cuộc họp. */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /** Ghi chú cá nhân liên quan đến tài liệu. */
    public function personalNotes()
    {
        return $this->hasMany(MeetingPersonalNote::class, 'meeting_document_id');
    }

    /** Người tạo. */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function documentField()
    {
        return $this->belongsTo(DocumentField::class, 'document_field_id');
    }

    public function issuingAgency()
    {
        return $this->belongsTo(IssuingAgency::class, 'issuing_agency_id');
    }

    public function documentSigner()
    {
        return $this->belongsTo(DocumentSigner::class, 'document_signer_id');
    }

    /** Người cập nhật. */
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Đăng ký Media Collection cho tài liệu đính kèm. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('meeting-document-files');
    }

    public function registerMediaConversions(?Media $media = null): void {}
}
