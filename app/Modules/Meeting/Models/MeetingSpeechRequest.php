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
        'meeting_participant_id',
        'meeting_agenda_id',
        'content',
        'status',
    ];

    /** Thành viên đăng ký phát biểu. */
    public function participant()
    {
        return $this->belongsTo(MeetingParticipant::class, 'meeting_participant_id');
    }

    /** Mục nghị sự liên quan (tùy chọn). */
    public function agenda()
    {
        return $this->belongsTo(MeetingAgenda::class, 'meeting_agenda_id');
    }
}
