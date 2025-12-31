<?php

namespace App\Livewire;

use App\Models\Installment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class CustomDataTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public ?float $totalAmount = null;

    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Installment::query())
            ->columns([
                TextColumn::make('family.name')
                    ->searchable(),
                TextColumn::make('transaction.id')
                    ->searchable(),
                TextColumn::make('invoice.id')
                    ->searchable(),
                TextColumn::make('account.name')
                    ->searchable(),
                TextColumn::make('installment_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('due_date', 'asc')
            ->paginationPageOptions([5,10, 25, 50, 100])
            ->summary('{{ table.summary.total }}');
    }

    protected function getTableContentFooter(): array
{
    // Retorna o seu componente de totalização
    return [
        $this->getTotalRow(),
    ];
}

    protected function getTotalRow(): \Filament\Tables\Columns\Layout\View
    {
        // 🚨 CHAVE: Obtém a query APÓS a aplicação de filtros 🚨
        $totalQuery = $this->getFilteredTableQuery();

        // 1. Realiza a soma no banco de dados sobre o conjunto de dados FILTRADO
        // Nota: Assumimos que 'amount' está em centavos.
        $totalAmountCents = $totalQuery->sum('amount_cents');
        $this->totalAmount = $this->getRecords()->count();;

        // 2. Retorna um componente View que irá renderizar a linha
        return \Filament\Tables\Columns\Layout\View::make('components.data-table-footer', [
            'total' => $this->totalAmount,
        ])->columnSpanFull();
    }

    public function render(): View
    {
        return view('livewire.custom-data-table');
    }

    protected function canViewAny(): bool
    {
        // Retorna true para permitir a visualização da tabela
        return true;
    }

    protected function canView(Model $record): bool
    {
        // Retorna true para permitir a visualização da linha
        return true;
    }

    /**
     * Permite a exibição do botão de "Criar" novo registro.
     */
    protected function canCreate(): bool
    {
        return true;
    }

    /**
     * Permite as ações de edição na linha.
     */
    protected function canEdit(Model $record): bool
    {
        return true;
    }

    /**
     * Permite as ações de exclusão na linha.
     */
    protected function canDelete(Model $record): bool
    {
        return true;
    }
}
