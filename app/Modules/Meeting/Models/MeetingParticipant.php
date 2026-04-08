<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingParticipant extends Model
{
    protected $table = 'm_participants';

    protected $fillable = ['meeting_id', 'user_id', 'role', 'position', 'status', 'checkin_at', 'absence_reason', 'delegated_to_id', 'sort_order', 'created_by', 'updated_by'];

    protected $casts = ['checkin_at' => 'datetime'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function delegatedTo()
    {
        return $this->belongsTo(User::class, 'delegated_to_id');
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
