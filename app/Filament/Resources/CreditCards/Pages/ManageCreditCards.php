<?php

namespace App\Filament\Resources\CreditCards\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\CreditCards\CreditCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;

class ManageCreditCards extends ManageRecords
{

    use HasToggleableTable;

    protected static string $resource = CreditCardResource::class;

//    protected function getHeaderActions(): array
//    {
//        return [
////            CreateAction::make(),
//        ];
//    }

}
