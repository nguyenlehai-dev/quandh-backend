<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAgenda extends Model
{
    use \App\Modules\Core\Traits\OrganizationScoped;

    use HasFactory;

    protected $table = 'm_agendas';

    protected $fillable = [
        'organization_id',
        'meeting_id',
        'title',
        'description',
        'order_index',
        'duration',
        'presenter_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'duration' => 'integer',
        'organization_id' => 'integer',
        'presenter_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /** Cuộc họp. */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /** Đăng ký phát biểu trong mục nghị sự. */
    public function speechRequests()
    {
        return $this->hasMany(MeetingSpeechRequest::class, 'meeting_agenda_id');
    }

    public function presenter()
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }

    /** Phiên biểu quyết liên quan. */
    public function votings()
    {
        return $this->hasMany(MeetingVoting::class, 'meeting_agenda_id');
    }

    /** Kết luận liên quan. */
    public function conclusions()
    {
        return $this->hasMany(MeetingConclusion::class, 'meeting_agenda_id');
    }
}
