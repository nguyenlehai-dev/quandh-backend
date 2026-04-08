<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingReminder extends Model
{
    protected $table = 'm_reminders';

    protected $fillable = ['meeting_id', 'user_id', 'title', 'content', 'remind_at', 'status'];

    protected $casts = ['remind_at' => 'datetime'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
