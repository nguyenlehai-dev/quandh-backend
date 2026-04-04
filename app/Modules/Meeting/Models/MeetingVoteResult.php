<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingVoteResult extends Model
{
    use HasFactory;

    protected $table = 'm_vote_results';

    protected $fillable = [
        'organization_id',
        'meeting_voting_id',
        'user_id',
        'choice',
    ];

    /** Phiên biểu quyết. */
    public function voting()
    {
        return $this->belongsTo(MeetingVoting::class, 'meeting_voting_id');
    }

    /** Người bỏ phiếu (null nếu ẩn danh trong response, nhưng DB vẫn lưu để chống trùng). */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
