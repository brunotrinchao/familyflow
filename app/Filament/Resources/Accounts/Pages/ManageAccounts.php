<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;

class ManageAccounts extends ManageRecords
{

    use HasToggleableTable;

    protected static string $resource = AccountResource::class;

//    protected function getHeaderActions(): array
//    {
//        return [
////            CreateAction::make(),
//        ];
//    }

}
