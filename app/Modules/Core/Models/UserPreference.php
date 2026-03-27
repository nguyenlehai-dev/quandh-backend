<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'current_organization_id',
        'notify_email',
        'notify_system',
        'notify_meeting_reminder',
        'notify_vote',
        'notify_document',
    ];

    protected $casts = [
        'notify_email' => 'boolean',
        'notify_system' => 'boolean',
        'notify_meeting_reminder' => 'boolean',
        'notify_vote' => 'boolean',
        'notify_document' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentOrganization()
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }
}
