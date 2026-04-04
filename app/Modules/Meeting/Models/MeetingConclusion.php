<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingConclusion extends Model
{
    use HasFactory;

    protected $table = 'm_conclusions';

    protected $fillable = [
        'organization_id',
        'meeting_id',
        'meeting_agenda_id',
        'title',
        'content',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(fn ($conclusion) => $conclusion->created_by = $conclusion->updated_by = auth()->id());
        static::updating(fn ($conclusion) => $conclusion->updated_by = auth()->id());
    }

    /** Cuộc họp. */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /** Mục nghị sự liên quan (tùy chọn). */
    public function agenda()
    {
        return $this->belongsTo(MeetingAgenda::class, 'meeting_agenda_id');
    }

    /** Người tạo. */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Người cập nhật. */
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
