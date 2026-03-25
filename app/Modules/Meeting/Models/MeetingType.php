<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingType extends Model
{
    use HasFactory;

    protected $table = 'm_meeting_types';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
