<?php

namespace App\Modules\Meeting\Models;

class Meeting extends BaseMeetingModel
{
    protected $table = 'm_meetings';

    protected $fillable = [
        'organization_id',
        'meeting_type_id',
        'code',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'status',
        'qr_token',
        'active_agenda_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id');
    }

    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class, 'meeting_id')->orderBy('sort_order')->orderBy('id');
    }

    public function agendas()
    {
        return $this->hasMany(MeetingAgenda::class, 'meeting_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeAgenda()
    {
        return $this->belongsTo(MeetingAgenda::class, 'active_agenda_id');
    }

    public function documents()
    {
        return $this->hasMany(MeetingDocument::class, 'meeting_id')->latest('id');
    }

    public function conclusions()
    {
        return $this->hasMany(MeetingConclusion::class, 'meeting_id')->latest('id');
    }

    public function speechRequests()
    {
        return $this->hasMany(MeetingSpeechRequest::class, 'meeting_id')->latest('id');
    }

    public function votings()
    {
        return $this->hasMany(MeetingVoting::class, 'meeting_id')->latest('id');
    }

    public function personalNotes()
    {
        return $this->hasMany(MeetingPersonalNote::class, 'meeting_id')->latest('id');
    }

    public function reminders()
    {
        return $this->hasMany(MeetingReminder::class, 'meeting_id')->latest('id');
    }

    public function scopeFilter($query, array $filters)
    {
        $sortBy = $filters['sort_by'] ?? 'start_at';
        $sortBy = in_array($sortBy, ['id', 'code', 'title', 'start_at', 'end_at', 'status', 'created_at', 'updated_at'], true) ? $sortBy : 'start_at';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->forCurrentOrganization()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(fn ($sub) => $sub->where('title', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['meeting_type_id'] ?? null, fn ($q, $id) => $q->where('meeting_type_id', $id))
            ->when($filters['from_date'] ?? null, fn ($q, $date) => $q->whereDate('start_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($q, $date) => $q->whereDate('start_at', '<=', $date))
            ->orderBy($sortBy, $sortOrder);
    }
}
