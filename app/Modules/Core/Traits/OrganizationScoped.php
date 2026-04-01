<?php

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait OrganizationScoped
{
    /**
     * Boot the organization scoped trait for a model.
     *
     * @return void
     */
    protected static function bootOrganizationScoped()
    {
        // 1. Add Global Scope
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = request()->header('X-Organization-Id');
            
            if ($organizationId) {
                // Phải kèm tên bảng để tránh lỗi ambiguous column khi join
                $builder->where($builder->getModel()->getTable() . '.organization_id', $organizationId);
            }
        });

        // 2. Tự động gán Organization ID khi tạo mới record
        static::creating(function (Model $model) {
            $organizationId = request()->header('X-Organization-Id');
            
            if ($organizationId && !isset($model->organization_id)) {
                $model->organization_id = $organizationId;
            }
        });
    }
}
