<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class MeetingPersonalNote extends Model
{
    protected $table = 'm_personal_notes';

    protected $fillable = ['meeting_id', 'document_id', 'user_id', 'content'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function document()
    {
        return $this->belongsTo(MeetingDocument::class, 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
