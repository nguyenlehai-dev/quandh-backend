<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingReminder extends Model
{
    use HasFactory;

    protected $table = 'm_reminders';

    protected $fillable = [
        'organization_id',
        'meeting_id',
        'channel',
        'remind_at',
        'status',
        'payload',
        'sent_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }
}
