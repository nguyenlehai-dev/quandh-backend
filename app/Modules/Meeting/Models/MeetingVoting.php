<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingVoting extends Model
{
    protected $table = 'm_votings';

    protected $fillable = ['meeting_id', 'agenda_id', 'title', 'description', 'type', 'status', 'options', 'opened_at', 'closed_at'];

    protected $casts = ['options' => 'array', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function agenda()
    {
        return $this->belongsTo(MeetingAgenda::class, 'agenda_id');
    }

    public function results()
    {
        return $this->hasMany(MeetingVoteResult::class, 'voting_id');
    }
}
