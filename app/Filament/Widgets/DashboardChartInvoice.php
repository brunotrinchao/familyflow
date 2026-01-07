<?php

namespace App\Filament\Widgets;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Installment;
use App\Services\DashboardWidgetService;
use Carbon\Carbon;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class DashboardChartInvoice extends ChartWidget
{
    use InteractsWithPageFilters;

    private Collection $dataInvoice;

    private string $title = "Faturas";


    protected int|string|array $columnSpan = [
        'default' => 12,
        'lg'      => 4
    ];

    protected static bool $isLazy = true;

//    protected ?string $maxHeight = '400px';

    protected function getData(): array
    {
        $service = app(DashboardWidgetService::class);
        $this->dataInvoice = $service->getDataInvoice($this->filters ?? []);

        $labels = [];
        $data = [];
        foreach ($this->dataInvoice as $creditCardName => $items) {
            $labels[] = $creditCardName;
            $data[] = $items->sum('total_amount');
        }

        $fixedColors = $service->colors;

        return [
            'datasets' => [
                [
                    'label'           => 'Faturas',
                    'data'            => $data,
                    'backgroundColor' => $fixedColors,
                    'hoverOffset'     => 4
                ],
            ],
            'labels'   => $labels
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): RawJs
    {
        return app(DashboardWidgetService::class)->getScriptDonnet($this->title);
    }
}
