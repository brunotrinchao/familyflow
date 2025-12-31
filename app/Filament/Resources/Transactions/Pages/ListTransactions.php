<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Resources\Transactions\Widgets\MonthTitleWidget;
use App\Models\Category;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\TransactionSeries;
use Carbon\Carbon;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ListTransactions extends ListRecords implements HasTable
{
    use HasFiltersForm;
    use ExposesTableToWidgets;

    protected static string $resource = TransactionResource::class;

    public string $currentMonthYear;

    public function getTableRecords(): \Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection
    {
        // 1. Obter e resolver os dados do filtro
        $activeFilters = $this->tableFilters;
        $filters = $activeFilters['filter'] ?? []; // Agora pega os valores do filtro

        // Extrai os valores das chaves do filtro
        $dates = $filters['invoice_date'] ?? null;
        $category = $filters['category'] ?? null;
        $type = $filters['type'] ?? null;
        $source = $filters['source'] ?? null;

        // Resolve datas (como o filtro não está aplicando o where, fazemos aqui)
        $date = Carbon::parse($dates ?? Carbon::now()->startOfMonth());
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        $monthYearLabel = $date->translatedFormat('F Y');
        $familyId = Filament::getTenant()?->id;

        $invoiceQuery = Invoice::query()
            ->where('family_id', $familyId)
            ->whereBetween('period_date', [
                $startDate,
                $endDate
            ])
            ->with(['creditCard']);

        // 🚨 Aplica os filtros na query de Installments aninhada à Invoice 🚨
        $invoiceQuery->whereHas('installments', function (Builder $qInstallment) use ($type, $category, $source) {
            $qInstallment->whereHas('transaction', function (Builder $qTransaction) use ($type, $category, $source) {
                // Aplicação Condicional
                $qTransaction->when($type, fn ($q, $v) => $q->where('type', $v));
                $qTransaction->when($category, fn ($q, $v) => $q->where('category_id', $v));
                $qTransaction->when($source, fn ($q, $v) => $q->where('source', $v));
            });
        });
        $invoices = $invoiceQuery->get()->map(function (Invoice $invoice) use ($monthYearLabel) {
            $cardName = $invoice->creditCard->name ?? 'Cartão Desconhecido';

            // Propriedade injetada: Descrição da Fatura
            $invoice->description = "Fatura {$cardName} ({$monthYearLabel})";
            $invoice->amount = $invoice->total_amount_cents;
            $invoice->source = $invoice->creditCard->brand;
            $invoice->category = null;
            // Propriedade injetada: Flag de identificação
            $invoice->is_invoice = true;

            return $invoice;
        });

        $installmentQuery = Installment::query()
            ->where('family_id', $familyId)
            ->whereNull('invoice_id')
            ->whereNotNull('account_id')
            ->whereBetween('due_date', [
                $startDate,
                $endDate
            ]) // Coluna CORRETA: due_date
            ->with([
                'transaction',
                'account'
            ]);

        // 🚨 Aplica os mesmos filtros na query de Installments 🚨
        $installmentQuery->whereHas('transaction', function (Builder $qTransaction) use ($type, $category, $source) {
            // Aplicação Condicional
            $qTransaction->when($type, fn ($q, $v) => $q->where('type', $v));
            $qTransaction->when($category, fn ($q, $v) => $q->where('category_id', $v));
            $qTransaction->when($source, fn ($q, $v) => $q->where('source', $v));
        });
        $installments = $installmentQuery->get()->map(function (Installment $installment) {
            $desc = $installment->transaction->description ?? 'Sem descrição';
            $installNum = $installment->installment_number;

            // Formata a descrição: Descrição + (Parcela X), se houver
            $installmentText = ($installNum > 1) ? " (Parc. {$installNum})" : '';

            $installment->description = $desc . $installmentText;
            $installment->amount = $installment->amount_cents;
            $installment->source = $installment->account->brand;
            $installment->category = $installment->transaction->category;
            $installment->is_invoice = false;

            return $installment;
        });

        // 4. Combinação e Ordenação
        $finalRecords = $invoices->toBase()
            ->merge($installments->toBase())
            ->sortByDesc(fn ($record) => $record->period_date ?? $record->due_date) // Ordena por data
            ->values();

        return $finalRecords;

    }

    //    public function getTabs(): array
    //    {
    //        return [
    //            'all'      => Tab::make('Todas'),
    //            'active'   => Tab::make('Pendentes')
    //                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatusEnum::PENDING)),
    //            'inactive' => Tab::make('Lançados')
    //                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TransactionStatusEnum::POSTED)),
    //        ];
    //    }

}
