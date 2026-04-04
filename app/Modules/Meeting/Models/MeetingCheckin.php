<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingCheckin extends Model
{
    use HasFactory;

    protected $table = 'm_checkins';

    protected $fillable = [
        'organization_id',
        'meeting_id',
        'meeting_participant_id',
        'type',
        'checked_in_by',
        'checked_in_at',
        'meta',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'meta' => 'array',
    ];
}
