<?php

namespace App\Filament\Resources\Families\Pages;

use App\Enums\ProfileUserEnum;
use App\Enums\RoleUserEnum;
use App\Filament\Resources\Families\FamilyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Actions\Impersonate;

class ListFamilies extends ListRecords
{
    protected static string $resource = FamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [
            Impersonate::make()->record($this->getRecord())
        ];
    }

    public function mount(): void
    {
        parent::mount();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Se não houver usuário ou não for Admin, encerra silenciosamente para listar as famílias
        if (!$user?->isSuperAdmin()) {
            return;
        }

        $adminFamily = $user->families()
            ->wherePivot('role', RoleUserEnum::ROLE_ADMIN)
            ->first();

        if ($adminFamily) {
            $this->redirect(
                static::$resource::getUrl('view', ['record' => $adminFamily->id])
            );
        }
    }
}
