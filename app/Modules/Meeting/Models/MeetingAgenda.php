<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingAgenda extends Model
{
    protected $table = 'm_agendas';

    protected $fillable = ['meeting_id', 'title', 'description', 'sort_order', 'duration_minutes', 'presenter_id', 'status'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function presenter()
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }
}
