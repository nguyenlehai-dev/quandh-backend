<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingPersonalNote extends Model
{
    use HasFactory;

    protected $table = 'm_personal_notes';

    protected $fillable = [
        'user_id',
        'meeting_id',
        'meeting_document_id',
        'content',
    ];

    /** Chủ sở hữu ghi chú. */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Cuộc họp. */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /** Tài liệu liên quan (tùy chọn). */
    public function document()
    {
        return $this->belongsTo(MeetingDocument::class, 'meeting_document_id');
    }

    /** Scope lọc ghi chú chỉ của user đang đăng nhập (cô lập dữ liệu). */
    public function scopeOwnedByAuth($query)
    {
        return $query->where('user_id', auth()->id());
    }
}
