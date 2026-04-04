<?php

namespace App\Modules\Meeting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingVoting extends Model
{
    use \App\Modules\Core\Traits\OrganizationScoped;

    use HasFactory;

    protected $table = 'm_votings';

    protected $fillable = [
        'organization_id',
        'meeting_id',
        'meeting_agenda_id',
        'title',
        'description',
        'type',
        'status',
        'opened_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

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

    /** Kết quả bỏ phiếu. */
    public function results()
    {
        return $this->hasMany(MeetingVoteResult::class, 'meeting_voting_id');
    }

    /** Kiểm tra phiên bỏ phiếu có phải ẩn danh hay không. */
    public function isAnonymous(): bool
    {
        return $this->type === 'anonymous';
    }
}
