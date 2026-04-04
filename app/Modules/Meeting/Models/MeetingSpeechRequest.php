<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingSpeechRequest extends Model
{
    use \App\Modules\Core\Traits\OrganizationScoped;

    use HasFactory;

    protected $table = 'm_speech_requests';

    protected $fillable = [
        'organization_id',
        'meeting_id',
        'meeting_participant_id',
        'meeting_agenda_id',
        'content',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /** Thành viên đăng ký phát biểu. */
    public function participant()
    {
        return $this->belongsTo(MeetingParticipant::class, 'meeting_participant_id');
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /** Mục nghị sự liên quan (tùy chọn). */
    public function agenda()
    {
        return $this->belongsTo(MeetingAgenda::class, 'meeting_agenda_id');
    }
}
