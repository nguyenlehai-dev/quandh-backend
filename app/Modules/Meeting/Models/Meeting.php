<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Meeting extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'm_meetings';

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(fn ($meeting) => $meeting->created_by = $meeting->updated_by = auth()->id());
        static::updating(fn ($meeting) => $meeting->updated_by = auth()->id());
    }

    /** Người tạo cuộc họp. */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Người cập nhật cuộc họp. */
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Danh sách thành viên (qua pivot mở rộng). */
    public function participants()
    {
        return $this->hasMany(MeetingParticipant::class, 'meeting_id');
    }

    /** Quan hệ nhiều-nhiều với users qua bảng pivot. */
    public function users()
    {
        return $this->belongsToMany(User::class, 'm_participants', 'meeting_id', 'user_id')
            ->withPivot('position', 'meeting_role', 'attendance_status', 'checkin_at', 'absence_reason')
            ->withTimestamps();
    }

    /** Chương trình nghị sự. */
    public function agendas()
    {
        return $this->hasMany(MeetingAgenda::class, 'meeting_id')->orderBy('order_index');
    }

    /** Tài liệu cuộc họp. */
    public function documents()
    {
        return $this->hasMany(MeetingDocument::class, 'meeting_id');
    }

    /** Kết luận cuộc họp. */
    public function conclusions()
    {
        return $this->hasMany(MeetingConclusion::class, 'meeting_id');
    }

    /** Phiên biểu quyết. */
    public function votings()
    {
        return $this->hasMany(MeetingVoting::class, 'meeting_id');
    }

    /** Ghi chú cá nhân. */
    public function personalNotes()
    {
        return $this->hasMany(MeetingPersonalNote::class, 'meeting_id');
    }

    /** Đăng ký Media Collection (nếu cần đính kèm trực tiếp vào meeting). */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('meeting-attachments');
    }

    public function registerMediaConversions(?Media $media = null): void {}

    /** Bộ lọc: search (title), status, from_date, to_date, sort_by, sort_order. */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('title', 'like', '%'.$search.'%');
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['from_date'] ?? null, function ($query, $fromDate) {
            $query->where('created_at', '>=', $fromDate);
        })->when($filters['to_date'] ?? null, function ($query, $toDate) {
            $query->where('created_at', '<=', $toDate.' 23:59:59');
        })->when($filters['sort_by'] ?? 'created_at', function ($query, $sortBy) use ($filters) {
            $allowed = ['id', 'title', 'start_at', 'created_at', 'updated_at'];
            $column = in_array($sortBy, $allowed) ? $sortBy : 'created_at';
            $query->orderBy($column, $filters['sort_order'] ?? 'desc');
        });
    }
}
