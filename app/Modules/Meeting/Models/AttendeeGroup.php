<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendeeGroup extends Model
{
    use HasFactory;

    protected $table = 'm_attendee_groups';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
