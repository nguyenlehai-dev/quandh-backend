<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'current_organization_id',
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
