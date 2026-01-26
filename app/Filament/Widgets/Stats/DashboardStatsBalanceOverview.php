<?php

namespace App\Filament\Widgets\Stats;

use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Services\DashboardWidgetService;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DashboardStatsBalanceOverview extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * ID único do gráfico.
     */
    protected static ?string $chartId = 'dashboardBalanceStats';

    /**
     * Ordem de exibição.
     */
    protected static ?int $sort = 3;

    /**
     * Largura das colunas.
     */
    protected int|string|array $columnSpan = [
        'default' => 12,
        'sm'      => 12,
        'md'      => 4,
        'lg'      => 4,
    ];

    /**
     * Altura do conteúdo.
     */
//    protected static ?int $contentHeight = 150;

    /**
     * Cabeçalho do widget.
     */
//    protected static ?string $heading = 'Saldo Disponível';

    /**
     * Lazy loading.
     */
    protected static bool $deferLoading = true;

    /**
     * Configurações do gráfico ApexCharts.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $service = app(DashboardWidgetService::class);
        $stats = $service->getSummaryStats($this->filters ?? []);

        // Calcular evolução do saldo (receitas - despesas acumulado)
//        $balanceHistory = $this->calculateBalanceHistory(
//            $stats['income_history'],
//            $stats['expense_history']
//        );

        $color = $stats['balance'] >= 0 ? '#3b82f6' : '#f59e0b'; // Azul ou Amarelo

        return [
            'chart' => [
                'type' => 'area',
                'height' => 100,
                'sparkline' => [
                    'enabled' => true,
                ],
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Saldo',
                    'data' => $stats['history'],
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'fill' => [
                'opacity' => 1,
            ],
            'colors' => [$color],
            'labels' => $stats['labels'],
            'xaxis' => [
                'type' => 'category',
                'labels' => [
                    'show' => false,
                ],
                'axisBorder' => [
                    'show' => false,
                ],
                'axisTicks' => [
                    'show' => false,
                ],
            ],
            'yaxis' => [
                'show' => false,
            ],
            'grid' => [
                'show' => false,
                'padding' => [
                    'top' => -20,
                    'right' => 0,
                    'bottom' => -8,
                    'left' => 0,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'title'    => [
                'text'  => $stats['balance_formatted'],
                'style' => [
                    'fontSize'   => '20px',
                    'fontWeight' => 'bold',
                        'cssClass' => 'apexcharts-yaxis-title'
                ],
            ],
            'subtitle' => [
                'text' => 'Soma do saldo das contas',
                        'cssClass' => 'apexcharts-yaxis-title'
            ],
        ];
    }

        protected function extraJsOptions(): ?\Filament\Support\RawJs
        {
        return app(DashboardWidgetService::class)->getExtraScriptStats();
    }
    /**
     * Calcula o histórico de saldo (receitas - despesas acumulado).
     *
     * @param array $incomeHistory
     * @param array $expenseHistory
     * @return array
     */
    private function calculateBalanceHistory(array $incomeHistory, array $expenseHistory): array
    {
        $balance = [];
        $accumulated = 0;

        foreach ($incomeHistory as $index => $income) {
            $expense = $expenseHistory[$index] ?? 0;
            $accumulated += ($income - $expense);
            $balance[] = $accumulated;
        }

        return $balance;
    }

    /**
     * Obtém o saldo total das contas.
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
     * Obtém o cabeçalho com saldo total das contas.
     *
     * @return string
     */
//    protected function getHeading(): string
//    {
//        $balance = $this->getTotalAccountBalance();
//        return MaskHelper::covertIntToReal($balance);
//    }

    /**
     * Obtém a descrição do widget.
     *
     * @return string|null
     */
    protected function getDescription(): ?string
    {
        $balance = $this->getTotalAccountBalance();

        if ($balance > 0) {
            return 'Saldo positivo';
        } elseif ($balance < 0) {
            return 'Atenção: Saldo negativo';
        }

        return 'Saldo zerado';
    }

    /**
     * Footer com informação adicional.
     *
     * @return string|null
     */
//    protected function getFooter(): ?string
//    {
//        return 'Soma do saldo das contas';
//    }
}
