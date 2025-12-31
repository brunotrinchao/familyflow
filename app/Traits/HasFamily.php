<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasFamily
{
    protected static function bootHasFamily(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->family_id) {

                $model->family_id =  Auth::user()->family_id;
            }
        });

        static::addGlobalScope('family', function (Builder $builder) {
            // Check if a user is authenticated before applying the scope
            if (Auth::check()) {
                $builder->where('family_id', auth()->user()->family_id);
            }
        });
    }
}
