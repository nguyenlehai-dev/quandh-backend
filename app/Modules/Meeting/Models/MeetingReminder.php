<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingReminder extends Model
{
    protected $table = 'm_reminders';

    protected $fillable = ['meeting_id', 'user_id', 'title', 'content', 'remind_at', 'status', 'created_by', 'updated_by'];

    protected $casts = ['remind_at' => 'datetime'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
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
