<?php

namespace App\Filament\Resources\Families\Schemas;

use App\Enums\ProfileUserEnum;
use App\Enums\RoleUserEnum;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Profiler\Profile;

class FamilyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('infolist')
                            ->heading('')
                            ->schema([
                                Grid::make()->schema([
                                    TextEntry::make('name')
                                        ->label('Nome')
                                        ->placeholder('-'),
                                    TextEntry::make('created_at')
                                        ->dateTime('d/m/Y H:i:s')
                                        ->placeholder('-')
                                        ->label('Criado em')

                                ])
                                    ->columns(3)
                            ])
                            ->columnSpan(5),
                        Section::make('infolist')
                            ->heading('')
                            ->schema([
                                Grid::make()->schema([
                                    TextEntry::make('admin')
                                        ->label('Admin')
                                        ->getStateUsing(function ($record): string {
                                            return $record?->admins->first()?->name ?? 'Sem Administrador';
                                        })
                                        ->badge()
                                        ->color(Color::Green),
                                    TextEntry::make('total')
                                        ->label('Total de membros')
                                        ->getStateUsing(function (Family $record): int {
                                            return $record->users()->count();
                                        })
                                ])
                            ])
                            ->columnSpan(5),
                        Section::make('infolist')
                            ->heading('')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Situação')
                                    ->getStateUsing(function (): string {
                                        $familyUser = FamilyUser::where('role', RoleUserEnum::ROLE_ADMIN->value)->first();
                                        return $familyUser->user->name ?? '-';
                                    })
                                    ->badge()
                                    ->color(Color::Green),

                            ])
                            ->columnSpan(2),
                    ])
            ]);
    }
}
