<?php

namespace App\Filament\Resources\Families\Schemas;

use App\Enums\ProfileUserEnum;
use App\Enums\UserStatusEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class FamilyMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->maxLength(255)
                            ->email()
                            ->disabled(),
                        Select::make('profile')
                            ->label('Perfil')
                            ->required()
                            ->options(
                            // 1. Obtém todos os pares [valor => rótulo] do Enum.
                                collect(ProfileUserEnum::cases())
                                    ->pluck('name', 'value') // Obtém ['super_admin' => 'Super Admin', 'member' => 'Membro', ...]
                                    ->filter(fn ($label, $value) => $value !== ProfileUserEnum::ROLE_SUPER_ADMIN->value)
                                    ->mapWithKeys(fn ($label,
                                                      $value) => [$value => ProfileUserEnum::tryFrom($value)?->getLabel() ?? $label])
                                    ->toArray()
                            )
                            ->default(ProfileUserEnum::ROLE_MEMBER)
                            ->mutateDehydratedStateUsing(fn (string $state): ProfileUserEnum => ProfileUserEnum::tryFrom($state)),
                        Radio::make('status')
                            ->label('Status')
                            ->inline()
                            ->required()
                            ->options(UserStatusEnum::class)
                            ->default(UserStatusEnum::ATI)
                    ])
                    ->columnSpanFull()
            ]);
    }
}
