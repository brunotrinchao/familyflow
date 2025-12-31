<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageCreditCardBrands extends ManageRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all'      => Tab::make('Todas'),
            'active'   => Tab::make('Banco')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'BANK')),
            'inactive' => Tab::make('Cartão')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'CREDITCARD')),
        ];
    }
}
