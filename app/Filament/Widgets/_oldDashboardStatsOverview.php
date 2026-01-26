<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Services\DashboardWidgetService;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class _oldDashboardStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'default' => 12,
    ];

    /**
     * Obtém as estatísticas do dashboard.
     *
     * @return array
     */
    protected function getStats(): array
    {
        $service = app(DashboardWidgetService::class);

        // Obter estatísticas do período
        $statsIncome = $service->getSummaryStats($this->filters ?? [], TransactionTypeEnum::INCOME);
        $statsExpense = $service->getSummaryStats($this->filters ?? [], TransactionTypeEnum::EXPENSE);

        $statsBalance = $service->getSummaryStats($this->filters ?? []);

        // Obter saldo das contas
        $accountBalance = $this->getTotalAccountBalance();

        return [
            $this->buildIncomeStat($statsIncome),
            $this->buildExpenseStat($statsExpense),
            $this->buildBalanceStat($statsBalance['balance']),
        ];
    }

    /**
     * Constrói o stat de receitas.
     *
     * @param array $stats
     * @return Stat
     */
    private function buildIncomeStat(array $stats): Stat
    {
        $label = $this->createLabelWithTooltip(
            'Receitas',
            'Valor total das receitas lançadas e pagas no período selecionado'
        );

        return Stat::make($label, $stats['total_formatted'])
            ->description("Total de {$stats['count']} transações")
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->icon(Iconoir::ArrowUpCircle)
            ->color('success')
            ->extraAttributes([
                'class' => 'widget-stats widget-stats-success',
            ]);
    }

    /**
     * Constrói o stat de despesas.
     *
     * @param array $stats
     * @return Stat
     */
    private function buildExpenseStat(array $stats): Stat
    {
        $label = $this->createLabelWithTooltip(
            'Despesas',
            'Valor total das despesas lançadas e pagas no período selecionado'
        );

        return Stat::make($label, $stats['total_formatted'])
            ->description("Total de {$stats['count']} transações")
            ->descriptionIcon('heroicon-m-arrow-trending-down')
            ->icon(Iconoir::ArrowDownCircle)
            ->color('danger')
            ->extraAttributes([
                'class' => 'widget-stats widget-stats-danger',
            ]);
    }

    /**
     * Constrói o stat de saldo disponível.
     *
     * @param int $balance
     * @return Stat
     */
    private function buildBalanceStat(int $balance): Stat
    {
        $label = $this->createLabelWithTooltip(
            'Saldo Disponível',
            'Soma do saldo de todas as contas da família'
        );

        $formatted = MaskHelper::covertIntToReal($balance);

        // Determinar cor baseado no saldo
        $color = $balance >= 0 ? 'primary' : 'warning';
        $icon = $balance >= 0 ? Iconoir::Wallet : 'heroicon-o-exclamation-triangle';

        return Stat::make($label, $formatted)
            ->description($this->getBalanceDescription($balance))
            ->descriptionIcon($balance >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
            ->icon($icon)
            ->color($color)
            ->extraAttributes([
                'class' => "widget-stats widget-stats-{$color}",
            ]);
    }

    /**
     * Obtém o saldo total das contas da família.
     *
     * @return int
     */
    private function getTotalAccountBalance(): int
    {
        $activeFamilyUser = Filament::getTenant();

        if (!$activeFamilyUser) {
            return 0;
        }

        return Account::whereHas('familyUser', function ($query) use ($activeFamilyUser) {
            $query->where('family_id', $activeFamilyUser->id);
        })->sum('balance');
    }

    /**
     * Cria um label com tooltip de ajuda.
     *
     * @param string $label
     * @param string $tooltipText
     * @return HtmlString
     */
    private function createLabelWithTooltip(string $label, string $tooltipText): HtmlString
    {
        return new HtmlString(
            "<div class='flex items-center gap-1.5'>
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
            </div>"
        );
    }

    /**
     * Obtém a descrição do saldo.
     *
     * @param int $balance
     * @return string
     */
    private function getBalanceDescription(int $balance): string
    {
        if ($balance > 0) {
            return 'Saldo positivo';
        } elseif ($balance < 0) {
            return 'Atenção: Saldo negativo';
        }

        return 'Saldo zerado';
    }

    /**
     * Atualiza o intervalo de polling.
     *
     * @return string|null
     */
    protected function getPollingInterval(): ?string
    {
        return '30s'; // Atualiza a cada 30 segundos
    }
}
