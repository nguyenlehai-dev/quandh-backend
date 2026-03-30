<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Document\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingType extends Model
{
    use \App\Modules\Core\Traits\MasterDataScoped;

    use HasFactory;

    protected $table = 'm_meeting_types';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /** Các nhóm người dự họp thuộc loại này. */
    public function attendeeGroups()
    {
        return $this->hasMany(AttendeeGroup::class, 'meeting_type_id');
    }

    /** Các loại tài liệu thuộc loại cuộc họp này. */
    public function documentTypes()
    {
        return $this->hasMany(DocumentType::class, 'meeting_type_id');
    }

    /** Các cuộc họp thuộc loại này. */
    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'meeting_type_id');
    }
}
