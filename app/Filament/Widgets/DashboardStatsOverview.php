<?php

namespace App\Filament\Widgets;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\FamilyUser;
use App\Models\Installment;
use Carbon\Carbon;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class DashboardStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = [
        'default' => 12
    ];

    protected function getStats(): array
    {
        $filters = $this->filters;

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
            ->with(['transaction'])
            ->get();

        $expenses = $installments->filter(fn ($item) => $item->transaction?->type === TransactionTypeEnum::EXPENSE
        );

        $incomes = $installments->filter(fn ($item) => $item->transaction?->type === TransactionTypeEnum::INCOME
        );

        $activeFamilyUser = Filament::getTenant();

        // Buscamos o saldo de todas as contas que pertencem à mesma família
        $totalAccountBalance = Account::whereHas('familyUser', function ($query) use ($activeFamilyUser) {
            $query->where('family_id', $activeFamilyUser->id);
        })->sum('balance');

        $getHelpIcon = function (string $label, string $tooltipText): HtmlString {
            return new HtmlString("
        <div class='flex items-center gap-1.5'>
            <span>{$label}</span>
            <span
                x-data='{}'
                x-tooltip.raw='{$tooltipText}'
                class='flex items-center cursor-help'
            >
                " . Blade::render("
                    <x-filament::icon
                        icon='heroicon-m-question-mark-circle'
                        class='w-4 h-4 text-gray-400 hover:text-gray-500 transition'
                    />
                ") . "
            </span>
        </div>
    ");
        };

        $stats[] = Stat::make($getHelpIcon('Receitas', 'Valor total das receitas Lançadas e Pagas no período selecionado'), MaskHelper::covertIntToReal($incomes->sum('amount'), true, false))
            ->icon(Iconoir::ArrowUpCircle)
            ->extraAttributes([
                'class' => 'widget-stats widget-stats-success',
            ])
            ->color('success');

        $stats[] = Stat::make($getHelpIcon('Despesas', 'Valor total das despesas Lançadas e Pagas no período selecionado'), MaskHelper::covertIntToReal($expenses->sum('amount'), true, false))
            ->icon(Iconoir::ArrowDownCircle)
            ->extraAttributes([
                'class' => 'widget-stats widget-stats-danger',
            ])
            ->color('danger');

        $stats[] = Stat::make($getHelpIcon('Saldo disponível', 'Soma do saldo das contas'), MaskHelper::covertIntToReal($totalAccountBalance))
            ->icon(Iconoir::Wallet)
            ->extraAttributes([
                'class' => 'widget-stats widget-stats-primary',
            ])
            ->color('primary');
        return $stats;
    }
}
