<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Actions\InstallmentActions;
use App\Filament\Resources\Installments\Schemas\InstallmentFormModal;
use App\Filament\Resources\Installments\Schemas\InstallmentsInfolist;
use App\Filament\Resources\Transactions\Schemas\TransactionFormChoice;
use App\Filament\Resources\Transactions\Schemas\TransactionFormModal;
use App\Filament\Resources\Transactions\Schemas\TransactionInfolistModal;
use App\Helpers\GeneralHelper;
use App\Helpers\MaskHelper;
use App\Models\Category;
use App\Models\Installment;
use App\Models\Transaction;
use App\Services\InstallmentGenerationService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class InstallmentRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';


    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return '';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('InvoiceId')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('InvoiceId')
            ->paginated(false)
            ->columns([
                TextColumn::make('transaction.date')
                    ->date('d/m')
                    ->label(''),
                TextColumn::make('title')
                    ->label('')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // CORREÇÃO: Usamos orWhereHas para não quebrar outros filtros da tabela
                        return $query->orWhereHas('transaction', function (Builder $query) use ($search) {
                            $query->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->description(fn ($record): ?string => $record?->transaction?->description)
                    ->getStateUsing(function (mixed $record): HtmlString {
                        // 1. Número da parcela atual (Ex: 3)
                        $currentInstallment = $record->number;
                        $totalInstallmentNumber = $record->transaction->installment_number;

                        $description = $record->transaction->title;

                        $html = "{$description} ";

                        if ($totalInstallmentNumber > 1) {
                            $html .= "<span class='text-xs text-gray-500 dark:text-gray-400'>({$currentInstallment}/{$totalInstallmentNumber})</span>";
                        }

                        return new HtmlString($html);
                    }),
                ViewColumn::make('icon')
                    ->label('')
                    ->view('components.category-icon-view')
                    ->viewData(fn (Installment $record) => [
                        'data'      => $record->transaction->category,
                        'showLabel' => true
                    ])
                    ->width('25%'),
                TextColumn::make('amount')
                    ->label('')
                    ->searchable()
                    ->money('BRL')
                    ->color(fn (Model $record) => $record->amount > 0 ? Color::Green : Color::Gray)
                    ->getStateUsing(function (mixed $record): string {
                        return MaskHelper::covertIntToReal($record->amount);
                    }),
                IconColumn::make('status')
                    ->label('')
                    ->tooltip(fn ($state) => $state->getLabel())
                    ->alignCenter()
            ])
            ->
            recordActions([
                InstallmentActions::makeViewInstallment()
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $record->invoice->status !== InvoiceStatusEnum::PAID,
            );
    }
}
