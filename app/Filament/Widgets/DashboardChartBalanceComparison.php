<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionTypeEnum;
use App\Services\DashboardWidgetService;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DashboardChartBalanceComparison extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * ID único do gráfico.
     */
    protected static ?string $chartId = 'balanceComparisonChart';

    /**
     * Ordem de exibição.
     */
    protected static ?int $sort = 7;

    /**
     * Largura das colunas.
     */
    protected int|string|array $columnSpan = [
        'default' => 12,
        'md'      => 12,
        'lg'      => 8,
    ];

    /**
     * Altura do conteúdo.
     */
//    protected static ?int $contentHeight = 350;

    /**
     * Cabeçalho do widget.
     */
//    protected static ?string $heading = 'Receitas vs Despesas';

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
        $statsIncome = $service->getSummaryStats($this->filters ?? [], TransactionTypeEnum::INCOME);
        $statsExpense = $service->getSummaryStats($this->filters ?? [], TransactionTypeEnum::EXPENSE);

        if ($statsIncome['total'] === 0 && $statsExpense['total'] === 0) {
            return $this->getEmptyChartOptions();
        }

        $colors = $service->getColors();

        $series = [
                [
                    'data' => [$statsIncome['total'] / 100],
                    'name' => 'Receitas',
                ],
                [
                    'data' => [$statsExpense['total'] / 100],
                    'name' => 'Despesas',
                ],
            ];

        $dueDate = !empty($this->filters['due_date'])
            ? Carbon::parse($this->filters['due_date'])
            : Carbon::now();



        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'toolbar' => [
                    'show' => false,
//                    'tools' => [
//                        'download' => true,
//                        'selection' => false,
//                        'zoom' => true,
//                        'zoomin' => true,
//                        'zoomout' => true,
//                        'pan' => false,
//                        'reset' => true,
//                    ],
                ],
//                'zoom' => [
//                    'enabled' => true,
//                ],
            ],
            'series' => $series,
            'xaxis' => [
                'categories' => [$dueDate->format('F \d\e Y')],
                'title' => [
                    'text' => 'Mês e Ano',
                ],
                'labels' => [
                    'rotate' => -45,
                    'rotateAlways' => false,
                ],
            ],
            'yaxis' => [
                'title' => [
                    'text' => 'Valor (R$)',
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'colors' => [
                $colors[2],
                $colors[1],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'legend' => [
                'show' => true,
                'position' => 'bottom',
                'horizontalAlign' => 'left',
                'fontSize' => '12px',
                'markers' => [
                    'width' => 12,
                    'height' => 12,
                    'radius' => 12,
                ],
            ],
            'grid' => [
                'show' => true,
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
            'markers' => [
                'size' => 4,
                'hover' => [
                    'size' => 6,
                ],
            ],
            'tooltip' => [
                'enabled' => true,
                'shared' => true,
                'intersect' => false,
            ],
            'title'    => [
                'text'  => 'Receitas vs Despesas',
                'style' => [
                    'fontSize'   => '20px',
                    'fontWeight' => 'bold'
                ],
            ],
        ];
    }

    /**
     * Retorna opções para gráfico vazio.
     *
     * @return array
     */
    private function getEmptyChartOptions(): array
    {
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
            ],
            'series' => [1],
            'labels' => ['Sem dados'],
            'colors' => ['#e5e7eb'],
            'legend' => [
                'show' => false,
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }

       protected function extraJsOptions(): ?\Filament\Support\RawJs
    {
        return app(DashboardWidgetService::class)->getExtraScriptStats();
    }

    /**
     * Obtém a descrição do widget.
     *
     * @return string|null
     */
    protected function getDescription(): ?string
    {
        $service = app(DashboardWidgetService::class);
        $stats = $service->getSummaryStats($this->filters ?? []);

        $balance = $stats['balance'];

        if ($balance > 0) {
            return "Saldo positivo: {$stats['balance_formatted']} 📈";
        } elseif ($balance < 0) {
            return "Atenção! Saldo negativo: {$stats['balance_formatted']} 📉";
        }

        return 'Saldo zerado';
    }
}
