<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Installment;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Illuminate\Support\Collection;

class DashboardWidgetService
{
    public array $colors = [];

    public function __construct()
    {
        $colors = Color::all();

        $this->colors = [
            $colors['blue'][500],
            $colors['red'][500],
            $colors['emerald'][500],
            $colors['rose'][500],
            $colors['amber'][400],
            $colors['sky'][500],
            $colors['indigo'][600],
            $colors['slate'][50],
            // Fundo de cards
            $colors['slate'][200],
        ];
    }

    public function getDataCategory(array $filters, TransactionTypeEnum $type): Collection
    {
        $dueDate = !empty($filters['due_date']) ? Carbon::parse($filters['due_date']) : null;

        if (!$dueDate) {
            $dueDate = Carbon::now();
        }

        $startDate = $dueDate->startOfMonth();
        $endDate = $dueDate->copy()->endOfMonth();

        $installments = Installment::query()
            ->whereBetween('due_date', [
                $startDate,
                $endDate
            ])
            ->whereIn('status', [
                InstallmentStatusEnum::POSTED,
                InstallmentStatusEnum::PAID
            ])
            ->whereHas('transaction', fn ($q) => $q->where('type', $type))
            ->with(['transaction.category'])
            ->get();

        return $installments->groupBy(fn ($item) => $item->transaction?->category?->name ?? 'Sem categoria');
    }

    public function getDataInvoice(array $filters): Collection
    {
        $dueDate = !empty($filters['due_date']) ? Carbon::parse($filters['due_date']) : null;

        if (!$dueDate) {
            $dueDate = Carbon::now();
        }

        $startDate = $dueDate->startOfMonth();
        $endDate = $dueDate->copy()->endOfMonth();

        $invoices = Invoice::query()
            ->whereBetween('period_date', [
                $startDate,
                $endDate
            ])->get();

        return $invoices->groupBy(fn ($item) => $item->creditCard->name);
    }

    public function getDailySpendingBySource(array $filters): Collection
    {
        $dueDate = !empty($filters['due_date']) ? Carbon::parse($filters['due_date']) : null;

        if (!$dueDate) {
            $dueDate = Carbon::now();
        }

        $startDate = $dueDate->startOfMonth();
        $endDate = $dueDate->copy()->endOfMonth();

        return Installment::query()
            ->whereBetween('due_date', [
                $startDate,
                $endDate
            ])
            ->with([
                'account',
                'transaction.creditCard'
            ]) // Carrega as fontes
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn ($item) => (int) $item->due_date->format('d'))
            ->map(function ($days) {
                return $days->groupBy(function ($installment) {
                    // Define o nome da fonte: ou o nome do cartão ou o nome da conta
                    return $installment->transaction->creditCard?->name ?? $installment->account?->name ?? 'Outros';
                })->map(fn ($sourceGroup) => $sourceGroup->sum('amount') / 100); // Converte centavos para Real
            });
    }

    public function getScriptDonnet($title): RawJs{
        return RawJs::make("
    {
        scales: {
            y: { grid: { display: false }, ticks: { display: false } },
            x: { grid: { display: false }, ticks: { display: false } }
        },
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            title: {
                display: true,
                text: '{$title}',
                padding: {
                    top: 10,
                    bottom: 30
                },
                font: {
                    size: 16
                },
            },
                legend: {
                    display: true,
                    align: 'left',
                    labels: {
                    position: 'top'
                    }
                },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed.y ?? context.parsed.x ?? context.parsed;

                        if (value === null || value === undefined) {
                            return label;
                        }

                        const valueFormatted = new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(value / 100);

                        const data = context.dataset.data;
                        const total = data.reduce((acc, val) => acc + val, 0);
                        const percent = ((value / total) * 100).toFixed(2);

                        return label + ': ' + valueFormatted + ' (' + percent + '%)';
                    }
                }
            }
        }
    }
    ");
    }
}
