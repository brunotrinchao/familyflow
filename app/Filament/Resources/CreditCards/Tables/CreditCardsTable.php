<?php

namespace App\Filament\Resources\CreditCards\Tables;

use App\Enums\Icon\Ionicons;
use App\Filament\Actions\SimpleActions;
use App\Filament\Resources\CreditCards\Schemas\CreditCardForm;
use App\Helpers\MaskHelper;
use App\Models\CreditCard;
use App\Services\CreditCardService;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Hydrat\TableLayoutToggle\Facades\TableLayoutToggle;

class CreditCardsTable
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
            ->filters([
                //
            ])

            ->recordUrl(null)
            ->recordAction('edit-modal')
            ->recordActions([
                SimpleActions::getViewWithEditAndDelete(
                    width         : Width::Large,
                    schemaCallback: fn (Schema $schema) => CreditCardForm::configure($schema),
                    actionCallback: function (array $data, CreditCard $record, Action $action): void {
                        if (CreditCard::where('name', $data['name'])
                            ->where('last_four_digits', $data['last_four_digits'])
                            ->whereNot('name', $record->name)
                            ->whereNot('last_four_digits', $record->name)->exists()) {

                            Notification::make('account_exist')
                                ->title('Conta já existente.')
                                ->warning()
                                ->send();
                            $action->halt();
                        }
                    },
                    recordName    : 'Cartão de crédito'
                ),
            ])
            ->toolbarActions([])
            ->headerActions([
                Action::make('create_creditcard')
                    ->icon(Iconoir::PlusCircle)
                    ->label('Cartão de crédito')
                    ->size(Size::ExtraLarge)
                    ->button()
                    ->color('primary')
                    ->modalIcon(Iconoir::Plus)
                    //                    ->modal()
                    ->modalWidth(Width::Small)
                    ->modalSubmitActionLabel('Cadastrar')
                    ->schema(fn (Schema $schema) => CreditCardForm::configure($schema))
                    ->action(function (array $data, \Filament\Actions\Action $action): void {
                        CreditCardService::create($data);
                    }),
                TableLayoutToggle::getToggleViewTableAction(compact: true)
                ->link(),
            ]);
    }

    public static function getListTableColumns(): array
    {
        return [
            ImageColumn::make('brand.icon_path')
                ->label('Bandeira')
                ->disk('public') // 🚨 CHAVE 1: Define o disco onde a imagem está salva
                ->visibility('public')
                ->width(30)
                ->imageHeight(40),
            TextColumn::make('name')
                ->label('Nome')
                ->searchable(),
            TextColumn::make('account.name')
                ->label('Conta')
                ->badge(),
            TextColumn::make('last_four_digits')
                ->label('Digitos')
                ->searchable(),
            TextColumn::make('user.name')
                ->label('Portador')
                ->sortable(),
            IconColumn::make('status')
                ->boolean(),
        ];
    }

    public static function getGridTableColumns(): array
    {
        return [
            Stack::make([
                ImageColumn::make('brand.icon_path')
                    ->label('Bandeira')
                    ->disk('public') // 🚨 CHAVE 1: Define o disco onde a imagem está salva
                    ->visibility('public')
                    ->width(30)
                    ->imageHeight(40)
                    ->circular()
                ->alignCenter(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                ->alignCenter(),
                TextColumn::make('account.name')
                    ->label('Conta')
                ->alignCenter(),
                TextColumn::make('last_four_digits')
                    ->label('Digitos')
                    ->searchable()
                ->alignCenter(),
                TextColumn::make('user.name')
                    ->label('Portador')
                    ->sortable()
                ->alignCenter(),
            ])
        ];
    }
}
