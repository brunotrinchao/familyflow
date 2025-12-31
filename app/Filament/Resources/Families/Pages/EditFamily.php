<?php

namespace App\Filament\Resources\Families\Pages;

use App\Enums\ProfileUserEnum;
use App\Enums\UserStatusEnum;
use App\Filament\Resources\Families\FamilyResource;
use App\Models\Family;
use App\Models\User;
use App\Services\UserService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditFamily extends EditRecord
{
    protected static string $resource = FamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            ViewAction::make(),
            DeleteAction::make()
                ->after(function() {
                    User::query()
                        ->where('family_id', Auth::user()->family_id)
                        ->whereNot('profile', ProfileUserEnum::ROLE_SUPER_ADMIN)
                        ->update([
                        'status' => UserStatusEnum::INA
                    ]);
                }),
//            ForceDeleteAction::make(),
            RestoreAction::make()->after(function() {
                User::query()
                    ->where('family_id', Auth::user()->family_id)
                    ->where('profile', ProfileUserEnum::ROLE_ADMIN)
                    ->whereNot('profile', ProfileUserEnum::ROLE_SUPER_ADMIN)
                    ->update([
                        'status' => UserStatusEnum::ATI
                    ]);
            }),
//            Action::make('active')
//                ->label('Ativar')
//                ->color('warning')
//                ->icon('heroicon-m-eye')
//                ->action(function (Family $record) {
//                    $record->restore();
//                })
        ];
    }
}
