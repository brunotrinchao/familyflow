<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Services\TenantContext;

trait BelongsToFamily
{
    protected static function bootBelongsToFamily()
    {
        static::creating(function ($model) {
            $familyUserId = app(TenantContext::class)->getFamilyUserId();
            if ($familyUserId) {
                $model->family_user_id = $familyUserId;
            }
        });

        static::addGlobalScope('family_user_scope', function (Builder $builder) {
            $familyUserId = app(TenantContext::class)->getFamilyUserId();
            if ($familyUserId) {
                $builder->where('family_user_id', $familyUserId);
            }
        });
    }
}
