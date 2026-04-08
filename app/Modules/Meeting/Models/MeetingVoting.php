<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingVoting extends Model
{
    protected $table = 'm_votings';

    protected $fillable = ['meeting_id', 'agenda_id', 'title', 'description', 'type', 'status', 'options', 'opened_at', 'closed_at', 'created_by', 'updated_by'];

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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
