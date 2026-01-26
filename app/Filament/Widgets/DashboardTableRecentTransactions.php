<?php

namespace App\Filament\Widgets;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Installment;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DashboardTableRecentTransactions extends TableWidget
{
     use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    /**
     * @return string|null
     */
    public function getMaxHeight(): ?string
    {
        return '400px';
    }

    protected int|string|array $columnSpan = [
        'default' => 12,
        'lg'      => 8,
    ];

    protected static ?string $heading = 'Últimas Transações';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('due_date')
                    ->label('Data')
                    ->date('d/m/Y'),

                TextColumn::make('transaction.title')
                    ->label('Descrição')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->transaction?->title),

                TextColumn::make('transaction.category.name')
                    ->label('Categoria')
                    ->badge(),
                TextColumn::make('source')
                    ->label('Fonte')
                    ->formatStateUsing(function ($record) {
                        return $record->transaction->creditCard?->name
                            ?? $record->account?->name
                            ?? 'N/A';
                    })
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn($state) => MaskHelper::covertIntToReal($state))
                    ->weight('bold')
                    ->alignEnd(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state->getLabel())
                    ->badge(),
            ])
            ->defaultSort('due_date', 'desc')
            ->paginated([4])
            ->striped()
            ->poll('30s');
    }
    /**
     * Obtém a query base da tabela.
     *
     * @return Builder
     */
    protected function getTableQuery(): Builder
    {
        $filters = $this->filters ?? [];
        $dueDate = !empty($filters['due_date'])
            ? Carbon::parse($filters['due_date'])
            : Carbon::now();

        $startDate = $dueDate->copy()->startOfMonth();
        $endDate = $dueDate->copy()->endOfMonth();

        return Installment::query()
            ->whereBetween('due_date', [$startDate, $endDate])
            ->with([
                'transaction.category',
                'transaction.creditCard',
                'account',
            ])
            ->orderBy('due_date', 'desc')
            ->limit(10);
    }

    /**
     * Verifica se pode visualizar o widget.
     *
     * @return bool
     */
    public static function canView(): bool
    {
        return true;
    }
}
