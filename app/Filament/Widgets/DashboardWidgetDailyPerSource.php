<?php

namespace App\Filament\Widgets;

use App\Services\DashboardWidgetService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DashboardWidgetDailyPerSource extends ChartWidget
{
    use InteractsWithPageFilters;


//    protected ?string $maxHeight = '600px';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'lg'      => 12
    ];

    protected function getData(): array
    {
        $service = app(DashboardWidgetService::class);
        $data = $service->getDailySpendingBySource($this->filters);

        // 1. Extrair todas as datas do período para o eixo X
        $labels = $data->keys()->toArray();

        $sources = $data->flatMap(fn($sources) => $sources->keys())->unique();

        $datasets = [];
        $colors = $service->colors;

        foreach ($sources as $index => $source) {
            $datasets[] = [
                'label' => $source,
                'data' => $data->map(fn($daySources) => (float)$daySources->get($source, 0))->values()->toArray(),
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)],
                'fill' => false,
                'tension' => 0.3, // Deixa a linha curvada
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => true,
            'plugins'             => [
                'title'   => [
                    'display' => true,
                    'text'    => 'Despesas por dia',
                    'padding' => [
                        'top'    => 10,
                        'bottom' => 30
                    ],
                    'font'    => [
                        'size' => 16
                    ],
                ],
                'legend ' => [
                    'display ' => true,
                    'align '   => 'left',
                    'labels '  => [
                        'position ' => 'top'
                    ]
                ],
            ],
        ];

    }
}
