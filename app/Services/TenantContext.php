<?php

namespace App\Services;

use App\Models\FamilyUser;
use Filament\Facades\Filament;

class TenantContext
{
    public function getFamilyId(): ?int
    {
        if (session()->has('active_family_id')) {
            return session('active_family_id');
        }

        return Filament::getTenant()?->id;
    }

    public function getFamilyUserId(): ?int
    {
        if (session()->has('active_family_user_id')) {
            return session('active_family_user_id');
        }

        $tenantId = Filament::getTenant()?->id;
        $userId = auth()->id();

        if (!$tenantId || !$userId) {
            return null;
        }

        return FamilyUser::where('user_id', $userId)
            ->where('family_id', $tenantId)
            ->value('id');
    }

    public function setFromFamilyUser(FamilyUser $familyUser): void
    {
        session([
            'active_family_user_id' => $familyUser->id,
            'active_family_id'      => $familyUser->family_id,
        ]);
    }
}
