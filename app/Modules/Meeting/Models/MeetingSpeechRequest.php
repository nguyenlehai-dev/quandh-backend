<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingSpeechRequest extends Model
{
    protected $table = 'm_speech_requests';

    protected $fillable = ['meeting_id', 'agenda_id', 'user_id', 'content', 'status', 'review_note', 'reviewed_by', 'reviewed_at', 'created_by', 'updated_by'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function agenda()
    {
        return $this->belongsTo(MeetingAgenda::class, 'agenda_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
