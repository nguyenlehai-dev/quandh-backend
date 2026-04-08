<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingVoteResult extends Model
{
    protected $table = 'm_vote_results';

    protected $fillable = ['voting_id', 'user_id', 'option', 'note', 'created_by', 'updated_by'];

    public function voting()
    {
        return $this->belongsTo(MeetingVoting::class, 'voting_id');
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
