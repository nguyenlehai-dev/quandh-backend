<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAgenda extends Model
{
    use HasFactory;

    protected $table = 'm_agendas';

    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'order_index',
        'duration',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'duration' => 'integer',
    ];

    /** Cuộc họp. */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /** Đăng ký phát biểu trong mục nghị sự. */
    public function speechRequests()
    {
        return $this->hasMany(MeetingSpeechRequest::class, 'meeting_agenda_id');
    }

    /** Phiên biểu quyết liên quan. */
    public function votings()
    {
        return $this->hasMany(MeetingVoting::class, 'meeting_agenda_id');
    }

    /** Kết luận liên quan. */
    public function conclusions()
    {
        return $this->hasMany(MeetingConclusion::class, 'meeting_agenda_id');
    }
}
