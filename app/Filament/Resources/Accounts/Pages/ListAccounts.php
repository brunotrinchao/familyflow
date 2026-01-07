<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\Accounts\Schemas\AccountForm;
use App\Filament\Resources\Accounts\Widgets\AccountOverview;
use App\Filament\Resources\Accounts\Widgets\AccountStatsOverviewWidget;
use App\Models\Account;
use App\Services\AccountService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            //            AccountStatsOverviewWidget::class
        ];
    }


    protected function getTableActions(): array
    {
        return [
            $this->getEditAccountAction(),
        ];
    }

    protected function getEditAccountAction(): Action
    {
        return Action::make('edit_account')
            ->modalWidth('sm')
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (Account $record) => $record->toArray())
            ->schema(fn (Schema $schema) => AccountForm::configure($schema))
            ->action(function (array $data, Account $record, Action $action): void {
                AccountService::update($data, $record, $action);
            });
    }

    protected function getTableRecordAction(): ?string
    {
        // Retorna o nome da ação ('edit_account') que deve ser executada ao clicar na linha
        return 'edit_account';
    }

    protected function getHeaderActions(): array
    {
        return [
            //            Action::make('create_account')
            //                ->icon('heroicon-s-plus')
            //                ->label('Nova conta')
            //                ->size(Size::ExtraLarge)
            //                ->button()
            //                ->color('primary')
            //                ->modal()
            //                ->modalWidth(Width::Small)
            //                ->modalSubmitActionLabel('Cadstrar')
            //                ->schema(fn (Schema $schema) => AccountForm::configure($schema)),
        ];
    }
}
