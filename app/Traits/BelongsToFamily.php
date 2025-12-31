<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToFamily
{
    protected static function bootBelongsToFamily()
    {
        static::creating(function ($model) {
            if (session()->has('active_family_user_id')) {
                $model->family_user_id = session('active_family_user_id');
            }
        });

        static::addGlobalScope('family_user_scope', function (Builder $builder) {
            if (session()->has('active_family_user_id')) {
                $builder->where('family_user_id', session('active_family_user_id'));
            }
        });
    }
}
