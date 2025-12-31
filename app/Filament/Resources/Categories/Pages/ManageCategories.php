<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Enums\CategoryTypeEnum;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Illuminate\Database\Eloquent\Builder;

class ManageCategories extends ManageRecords
{
    use HasToggleableTable;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
//            CategoryOverview::class,
        ];
    }

    //    protected function getHeaderActions(): array
//    {
//        return [
//            CreateAction::make()
//            ->icon('heroicon-s-plus')
//            ->label('Nova categoria')
//                ->size(Size::ExtraLarge)
//            ->button()
//            ->color('primary'),
//        ];
//    }

    public function getTabs(): array
    {
        return [
            'all'      => Tab::make('Todas'),
            'expense'   => Tab::make(CategoryTypeEnum::EXPENSE->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', CategoryTypeEnum::EXPENSE)),
            'income' => Tab::make(CategoryTypeEnum::INCOME->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', CategoryTypeEnum::INCOME)),
        ];
    }
}
