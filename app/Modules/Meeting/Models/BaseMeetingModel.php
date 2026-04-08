<?php

namespace App\Modules\Meeting\Models;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class BaseMeetingModel extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            if ($model->isFillable('organization_id') && ! $model->organization_id) {
                $model->organization_id = function_exists('getPermissionsTeamId') ? getPermissionsTeamId() : null;
            }

            if ($model->isFillable('created_by')) {
                $model->created_by = auth()->id();
            }

            if ($model->isFillable('updated_by')) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function (Model $model) {
            if ($model->isFillable('updated_by')) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForCurrentOrganization($query)
    {
        $organizationId = function_exists('getPermissionsTeamId') ? getPermissionsTeamId() : null;

        return $query->when($organizationId, fn ($q) => $q->where('organization_id', (int) $organizationId));
    }

    public function scopeSearchByName($query, ?string $search)
    {
        return $query->when($search, fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
    }
}
