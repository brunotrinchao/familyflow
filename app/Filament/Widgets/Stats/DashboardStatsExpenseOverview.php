<?php

namespace App\Filament\Widgets\Stats;

use App\Enums\TransactionTypeEnum;
use App\Services\DashboardWidgetService;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DashboardStatsExpenseOverview extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * ID único do gráfico.
     */
    protected static ?string $chartId = 'dashboardExpenseStats';

    /**
     * Ordem de exibição.
     */
    protected static ?int $sort = 2;

    /**
     * Largura das colunas.
     */
    protected int|string|array $columnSpan = [
        'default' => 12,
        'sm'      => 6,
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
    //    protected static ?string $heading = 'Despesas';

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
        $stats = $service->getSummaryStats($this->filters ?? [], TransactionTypeEnum::EXPENSE);

        return [
            'chart'      => [
                'type'      => 'area',
                'height'    => 100,
                'sparkline' => [
                    'enabled' => true,
                ],
                'toolbar'   => [
                    'show' => false,
                ],
            ],
            'series'     => [
                [
                    'name' => 'Despesas',
                    'data' => $stats['history'],
                ],
            ],
            'stroke'     => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'fill' => [
                'opacity' => 1,
            ],
            'colors'     => ['#ef4444'],
            // Vermelho (red-500)
            'labels'     => $stats['labels'],
            'xaxis'      => [
                'type'       => 'category',
                'labels'     => [
                    'show' => false,
                ],
                'axisBorder' => [
                    'show' => false,
                ],
                'axisTicks'  => [
                    'show' => false,
                ],
            ],
            'yaxis'      => [
                'show' => false,
            ],
            'grid'       => [
                'show'    => false,
                'padding' => [
                    'top'    => -20,
                    'right'  => 0,
                    'bottom' => -8,
                    'left'   => 0,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'title'      => [
                'text'  => $stats['total_formatted'],
                'style' => [
                    'fontSize'   => '20px',
                    'fontWeight' => 'bold'
                ],
            ],
            'subtitle'   => [
                'text' => 'Despesas do Período',
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return app(DashboardWidgetService::class)->getExtraScriptStats();
    }

    /**
     * Obtém o cabeçalho com valor total.
     *
     * @return string
     */
    //    protected function getHeading(): string
    //    {
    //        $service = app(DashboardWidgetService::class);
    //        $stats = $service->getSummaryStats($this->filters ?? []);
    //
    //        return $stats['total_expenses_formatted'];
    //    }

    /**
     * Obtém a descrição do widget.
     *
     * @return string|null
     */
    protected function getDescription(): ?string
    {
        $service = app(DashboardWidgetService::class);
        $stats = $service->getSummaryStats($this->filters ?? []);

        return "{$stats['expenses_count']} despesas no período";
    }

    /**
     * Footer com informação adicional.
     *
     * @return string|null
     */
    //    protected function getFooter(): ?string
    //    {
    //        return 'Despesas lançadas e pagas';
    //    }
}
