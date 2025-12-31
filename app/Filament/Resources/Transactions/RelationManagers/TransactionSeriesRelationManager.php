<?php

namespace App\Filament\Resources\Transactions\RelationManagers;

use App\Enums\TransactionStatusEnum;
use App\Filament\Actions\SimpleActions;
use App\Filament\Resources\Transactions\Schemas\TransactionSerieFormModal;
use App\Helpers\MaskHelper;
use App\Models\TransactionSeries;
use App\Services\TransactionSeriesService;
use App\Services\TransactionService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TransactionSeriesRelationManager extends RelationManager
{
    protected static string $relationship = 'transactionSeries';

    protected static ?string $title = 'Parcelas';

    public function isReadOnly(): bool
    {
        return false;
    }

    protected $listeners = [
        'refreshTransactionSeries' => '$refresh',
    ];

    public function form(Schema $schema): Schema
    {
        return TransactionSerieFormModal::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('installment')
                    ->label('Parcela')
                    ->numeric()
                    ->size(TextSize::Large),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->getStateUsing(function ($record) {
                        return MaskHelper::covertIntToReal($record->amount);
                    })
                    ->size(TextSize::Large),
                TextColumn::make('date')
                    ->label('Data')
                    ->getStateUsing(function ($record) {
                        return Carbon::parse($record->date)->format('d/m/Y');
                    })
                    ->size(TextSize::Large),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([
                SimpleActions::getViewWithEditAndDelete(
                    width         : Width::Medium,
                    schemaCallback: fn (Schema $schema) => TransactionSerieFormModal::configure($schema),
                    actionCallback: function (array $data, TransactionSeries $record, Action $action): void {
                        $transaction = $record->transaction;
                        TransactionSeriesService::synchronizeSeries($transaction);
                    },
                    recordName    : 'Parcela'
                ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //                    DissociateBulkAction::make(),
                    //                    DeleteBulkAction::make(),
                    BulkAction::make('delete')
                        ->label('Excluir parcelas')
                        ->icon('heroicon-m-trash')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(function ($records) {
                            $seriesDeleted = 0;

                            $transaction = $records->first()->transaction;
                            $unpaidItems = $transaction->transactionSeries()->where('status', '=', TransactionStatusEnum::PENDING);

                            foreach ($records as $record) {

                                if ($record->status === TransactionStatusEnum::POSTED) {
                                    continue; // Ignora parcelas já pagas
                                }


                                if ($unpaidItems->count() === 1 && $unpaidItems->first()->id === $record->id) {
                                    continue; // Ignora exclusão da última parcela não paga
                                }

                                $record->delete();
                                $seriesDeleted++;
                            }
                            $data = $transaction->toArray();
                            $data['installment_number'] = $data['installment_number'] - $seriesDeleted;
                            $data['amount'] = MaskHelper::covertIntToReal($data['amount'], false);
                            unset($data['id'], $data['family_id'], $data['user_id'], $data['updated_at'], $data['created_at']);

                            TransactionService::update($transaction, $data);

                            Notification::make()
                                ->title('Parcelas excluídas com sucesso!')
                                ->success()
                                ->send();


                            $this->dispatch('refreshTransactionSeries');
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),

            ]);
    }

    public function getTabs(): array
    {
        return [
            'all'      => Tab::make('Todas'),
            'active'   => Tab::make('Pendentes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatusEnum::PENDING)),
            'inactive' => Tab::make('Lançados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatusEnum::POSTED)),
        ];
    }
}
