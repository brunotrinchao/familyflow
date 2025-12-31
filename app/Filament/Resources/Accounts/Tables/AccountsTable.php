<?php

namespace App\Filament\Resources\Accounts\Tables;

use App\Enums\AccountTypeEnum;
use App\Enums\BankEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Actions\SimpleActions;
use App\Filament\Resources\Accounts\Schemas\AccountForm;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Services\AccountService;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        /** @var \Hydrat\TableLayoutToggle\Concerns\HasToggleableTable $livewire */
        $livewire = $table->getLivewire();

        return $table
            ->columns(
                $livewire->isGridLayout()
                    ? static::getGridTableColumns()
                    : static::getListTableColumns()
            )
            ->contentGrid(
                fn () => $livewire->isListLayout()
                    ? null
                    : [
                        'md' => 2,
                        'lg' => 4,
                        'xl' => 5,
                    ]
            )
            ->recordUrl(null)
            ->recordAction('edit-modal')
            ->recordTitleAttribute('Conta')
            ->recordClasses('compact-table')
            ->filters([
                Filter::make('filter')
                    ->schema([
                        Select::make('type')
                            ->label('Tipo')
                            ->options(AccountTypeEnum::class),
                        Select::make('bank')
                            ->label('Banco')
                            ->options(BankEnum::class)
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data) {
                        $type = $data['type'] ?? null;
                        $bank = $data['bank'] ?? null;

                        $query->when($type, fn ($q) => $q->where('type', $type));

                        $query->when($bank, fn ($q) => $q->where('bank', $bank));
                    })
            ])
            ->recordActions([
                SimpleActions::getViewWithEditAndDelete(
                    width         : Width::Small,
                    schemaCallback: fn (Schema $schema) => AccountForm::configure($schema),
                    actionCallback: function (array $data, Account $record, Action $action): void {

                        if (!empty($data['account']) ?? Account::where('account', $data['account'])
                            ->whereNot('account', $record->account)->exists()) {

                            Notification::make('account_exist')
                                ->title('Conta já existente.')
                                ->warning()
                                ->send();
                            $action->halt();
                        }

                        $success = app(AccountService::class)->update($record, $data);

                        if ($success) {
                            Notification::make()
                                ->title('Conta atualizada com sucesso!')
                                ->success()
                                ->send();
                            $action->cancel();
                        }
                    },
                    recordName    : 'Conta'
                ),
            ])
            ->toolbarActions([])
            ->headerActions([
                Action::make('create_account')
                    ->icon(Iconoir::PlusCircle)
                    ->label('Conta')
                    ->size(Size::ExtraLarge)
                    ->button()
                    ->color('primary')
                    ->modalIcon(Iconoir::Plus)
                    //                    ->modal()
                    ->modalWidth(Width::Small)
                    ->modalSubmitActionLabel('Cadastrar')
                    ->schema(fn (Schema $schema) => AccountForm::configure($schema))
                    ->action(function (array $data, \Filament\Actions\Action $action): void {
                        AccountService::create($data);
                    }),
            ]);
    }

    public static function getListTableColumns(): array
    {
        return [
            ImageColumn::make('brand.icon_path')
                ->label('Banco')
                ->disk('public') // 🚨 CHAVE 1: Define o disco onde a imagem está salva
                ->visibility('public')

                ->width(30)
                ->imageHeight(40)
                ->circular(),
            TextColumn::make('name')
                ->label('Conta')
                ->searchable(),
            TextColumn::make('balance')
                ->label('Saldo')
                ->money('BRL')
                ->summarize(Sum::make()->money('BRL', locale: 'pt_BR', divideBy: 100)->label('Saldo totdal'))
            ->getStateUsing(function (mixed $record): string {
                        return MaskHelper::covertIntToReal($record->balance);
                    }),
        ];
    }

    // Define the columns for the table when displayed in grid layout
    public static function getGridTableColumns(): array
    {
        return [
            Stack::make([
                ImageColumn::make('brand.icon_path')
                    ->label('Banco')
                    ->disk('public') // 🚨 CHAVE 1: Define o disco onde a imagem está salva
                    ->visibility('public')
                    ->width(30)
                    ->imageHeight(40)
                    ->circular()
                    ->alignCenter(),
                TextColumn::make('name')
                    ->label('Conta')
                    ->searchable()
                    ->alignCenter(),
                TextColumn::make('balance')
                    ->label('Tipo')
                    ->money('BRL')
                    ->alignCenter(),
            ])
                ->space(1)->extraAttributes([
                    'class' => 'pb-2',
                ])
        ];
    }
}
