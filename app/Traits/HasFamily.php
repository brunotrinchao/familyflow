<?php

namespace App\Traits;

use App\Models\Category;
use App\Models\Family;
use App\Models\FamilyUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\TenantContext;

trait HasFamily
{
    protected static function bootHasFamily(): void
    {

        static::creating(function ($model) {
            $familyId = app(TenantContext::class)->getFamilyId();
            if ($familyId) {
                $model->family_id = $familyId;
            }
        });

        static::addGlobalScope('family_user_scope', function (Builder $builder) {
            $familyId = app(TenantContext::class)->getFamilyId();
            if ($familyId) {

                if (!$familyId) {
                    return;
                }

                // Verifica se o Model que está chamando o scope é o de Categoria
                if ($builder->getModel() instanceof Category) {
                    $builder->where(function (Builder $query) use ($familyId) {
                        $query->whereNull('family_id')
                            ->orWhere('family_id', $familyId);
                    });
                } else {
                    // Lógica padrão para os demais Models
                    $builder->where('family_id', $familyId);
                }
            }
        });
    }
}
