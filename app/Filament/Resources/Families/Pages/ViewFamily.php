<?php

namespace App\Filament\Resources\Families\Pages;

use App\Enums\ProfileUserEnum;
use App\Filament\Resources\Families\FamilyResource;
use App\Models\Family;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ViewFamily extends ViewRecord
{
    protected static string $resource = FamilyResource::class;


    public function getTitle(): string|Htmlable
    {
        return __('custom.title.family') . ' ' . filament()->getTenant()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageSubscription')
                ->label('Gerenciar Assinatura')
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
                ->size(Size::ExtraLarge)
                ->action(function (Family $record) {
                    // Redireciona o usuário para o Billing Portal hospedado pelo Stripe
                    return $record->redirectToBillingPortal();
                })
                // Ação só deve ser visível para o Admin da Família
                ->visible(fn () => auth()->user()->profile === ProfileUserEnum::ROLE_ADMIN),
            EditAction::make()
                ->icon(Iconoir::EditPencil)
                ->size(Size::ExtraLarge)
                ->color(Color::Amber)
                ->modal(true)
                ->modalHeading('Edit Family'),
            Action::make('return_transaction')
                ->icon(Iconoir::ArrowLeft)
                ->size(Size::ExtraLarge)
                ->color(Color::Slate)
                ->url(fn (Family $record): string => FamilyResource::getUrl('index'))
                ->label('Voltar')
        ];
    }
}
