<?php

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait MasterDataScoped
{
    /**
     * Boot the master data scoped trait for a model.
     * Master Data models can be queried if organization_id IS NULL (System Defaults) 
     * OR organization_id = [X-Organization-Id] (Specific Org Customs).
     *
     * @return void
     */
    protected static function bootMasterDataScoped()
    {
        // 1. Add Global Scope
        static::addGlobalScope('organization_master', function (Builder $builder) {
            $organizationId = request()->header('X-Organization-Id');
            
            if ($organizationId) {
                // Cho phép lấy dữ liệu của chính Organization đó và dữ liệu dùng chung (organization_id IS NULL)
                $builder->where(function($q) use ($builder, $organizationId) {
                    $q->where($builder->getModel()->getTable() . '.organization_id', $organizationId)
                      ->orWhereNull($builder->getModel()->getTable() . '.organization_id');
                });
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
