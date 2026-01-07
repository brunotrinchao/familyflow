<?php

namespace App\Traits;

use App\Models\Category;
use App\Models\Family;
use App\Models\FamilyUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasFamily
{
    protected static function bootHasFamily(): void
    {

        static::creating(function ($model) {
            if (session()->has('active_family_user_id')) {
                $familyUSerId = FamilyUser::find(session('active_family_user_id'))->family_id;
                $model->family_id = $familyUSerId;
            }
        });

        static::addGlobalScope('family_user_scope', function (Builder $builder) {
            if (session()->has('active_family_user_id')) {
                // Busca o ID da família na sessão (idealmente use cache ou carregue uma vez)
                $familyId = FamilyUser::find(session('active_family_user_id'))?->family_id;

                if (!$familyId) return;

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
