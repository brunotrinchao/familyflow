<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Installment;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardWidgetService
{
    private const CACHE_TTL = 300; // 5 minutos
    private const DEFAULT_CATEGORY = 'Sem categoria';
    private const DEFAULT_SOURCE = 'Outros';

    private array $colors;

    public function __construct()
    {
        $this->initializeColors();
    }

    /**
     * Inicializa as cores do dashboard.
     *
     * @return void
     */
    private function initializeColors(): void
    {
        $colors = Color::all();

        $this->colors = [
            'blue'    => $colors['blue'][500],
            'red'     => $colors['red'][500],
            'emerald' => $colors['emerald'][500],
            'rose'    => $colors['rose'][500],
            'amber'   => $colors['amber'][400],
            'sky'     => $colors['sky'][500],
            'indigo'  => $colors['indigo'][600],
            'light'   => $colors['slate'][50],
            'card_bg' => $colors['slate'][200],
        ];
    }

    /**
     * Obtém as cores disponíveis.
     *
     * @return array
     */
    public function getColors(): array
    {
        return array_values($this->colors);
    }

    /**
     * Obtém dados agrupados por categoria.
     *
     * @param array $filters
     * @param TransactionTypeEnum $type
     * @param bool $useCache
     * @return Collection
     */
    public function getDataCategory(
        array               $filters,
        TransactionTypeEnum $type,
        bool                $useCache = true
    ): Collection
    {
        try {
            $dateRange = $this->extractDateRange($filters);

            if ($useCache) {
                $cacheKey = $this->generateCacheKey('category', $dateRange, $type->value);

                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateRange, $type) {
                    return $this->fetchCategoryData($dateRange, $type);
                });
            }

            return $this->fetchCategoryData($dateRange, $type);

        } catch (\Exception $e) {
            Log::error('Erro ao obter dados por categoria.', [
                'message' => $e->getMessage(),
                'filters' => $filters,
                'type'    => $type->value,
            ]);

            return collect();
        }
    }

    /**
     * Obtém dados agrupados por fatura (cartão).
     *
     * @param array $filters
     * @param bool $useCache
     * @return Collection
     */
    public function getDataInvoice(array $filters, bool $useCache = true): Collection
    {
        try {
            $dateRange = $this->extractDateRange($filters);

            if ($useCache) {
                $cacheKey = $this->generateCacheKey('invoice', $dateRange);

                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateRange) {
                    return $this->fetchInvoiceData($dateRange);
                });
            }

            return $this->fetchInvoiceData($dateRange);

        } catch (\Exception $e) {
            Log::error('Erro ao obter dados de faturas.', [
                'message' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return collect();
        }
    }

    /**
     * Obtém gastos diários agrupados por fonte (cartão/conta).
     *
     * @param array $filters
     * @param bool $useCache
     * @return Collection
     */
    public function getDailySpendingBySource(
        array $filters,
        bool  $useCache = true
    ): Collection
    {
        try {
            $dateRange = $this->extractDateRange($filters);

            if ($useCache) {
                $cacheKey = $this->generateCacheKey('daily_spending', $dateRange);

                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dateRange) {
                    return $this->fetchDailySpendingData($dateRange);
                });
            }

            return $this->fetchDailySpendingData($dateRange);

        } catch (\Exception $e) {
            Log::error('Erro ao obter gastos diários.', [
                'message' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return collect();
        }
    }

    /**
     * Busca dados de categorias no banco.
     *
     * @param array $dateRange
     * @param TransactionTypeEnum $type
     * @return Collection
     */
    private function fetchCategoryData(
        array               $dateRange,
        TransactionTypeEnum $type
    ): Collection
    {
        $installments = $this->fetchInstallmente($dateRange, $type);

        return $installments->groupBy(function ($item) {
            return $item->transaction?->category?->name ?? self::DEFAULT_CATEGORY;
        })->map(function ($group) {
            return [
                'total' => abs($group->sum('amount')),
                'count' => $group->count(),
                'items' => $group,
            ];
        });
    }

    private function fetchInstallmente($dateRange, ?TransactionTypeEnum $type): \Illuminate\Database\Eloquent\Collection
    {
        $typeModify = $type ? [$type] : array_column(TransactionTypeEnum::cases(), 'value');
        $data = Installment::query()
            ->whereBetween('due_date', [
                $dateRange['start'],
                $dateRange['end']
            ])
            ->whereIn('status', [
                InstallmentStatusEnum::POSTED,
                InstallmentStatusEnum::PAID
            ])
            ->when($type, function ($query) use ($typeModify) {
                $query->whereHas('transaction', fn ($q) => $q->whereIn('type', $typeModify));
            })
            ->with([
                'transaction',
                'transaction.category'
            ])
            ->orderBy('due_date');
        return $data->get();
    }

    /**
     * Busca dados de faturas no banco.
     *
     * @param array $dateRange
     * @return Collection
     */
    private function fetchInvoiceData(array $dateRange): Collection
    {
        $invoices = Invoice::query()
            ->whereBetween('period_date', [
                $dateRange['start'],
                $dateRange['end']
            ])
            ->with([
                'creditCard',
                'installments'
            ])
            ->get();

        return $invoices->groupBy(function ($item) {
            return $item->creditCard->name;
        })->map(function ($group) {
            return [
                'total'           => abs($group->sum('total_amount')),
                'count'           => $group->count(),
                'total_formatted' => $this->formatCurrency($group->sum('total_amount')),
                'items'           => $group,
            ];
        });
    }

    /**
     * Busca dados de gastos diários no banco.
     *
     * @param array $dateRange
     * @return Collection
     */
    private function fetchDailySpendingData(array $dateRange): Collection
    {
        $installments = Installment::query()
            ->whereBetween('due_date', [
                $dateRange['start'],
                $dateRange['end']
            ])
            ->whereIn('status', [
                InstallmentStatusEnum::POSTED,
                InstallmentStatusEnum::PAID
            ])
            ->with([
                'account',
                'transaction.creditCard'
            ])
            ->orderBy('due_date')
            ->get();

        return $installments
            ->groupBy(fn ($item) => (int)$item->due_date->format('d'))
            ->map(function ($dayGroup) {
                return $dayGroup
                    ->groupBy(function ($installment) {
                        return $this->getSourceName($installment);
                    })
                    ->map(function ($sourceGroup) {
                        return [
                            'amount'           => abs($sourceGroup->sum('amount')),
                            'amount_formatted' => $this->formatCurrency($sourceGroup->sum('amount')),
                            'count'            => $sourceGroup->count(),
                        ];
                    });
            });
    }

    /**
     * Obtém o nome da fonte (cartão ou conta).
     *
     * @param Installment $installment
     * @return string
     */
    private function getSourceName(Installment $installment): string
    {
        return $installment->transaction->creditCard?->name
            ?? $installment->account?->name
            ?? self::DEFAULT_SOURCE;
    }

    /**
     * Extrai o intervalo de datas dos filtros.
     *
     * @param array $filters
     * @return array
     */
    private function extractDateRange(array $filters): array
    {
        $dueDate = !empty($filters['due_date'])
            ? Carbon::parse($filters['due_date'])
            : Carbon::now();

        return [
            'start' => $dueDate->copy()->startOfMonth(),
            'end'   => $dueDate->copy()->endOfMonth(),
            'date'  => $dueDate,
        ];
    }

    /**
     * Gera uma chave de cache única.
     *
     * @param string $prefix
     * @param array $dateRange
     * @param string|null $suffix
     * @return string
     */
    private function generateCacheKey(
        string  $prefix,
        array   $dateRange,
        ?string $suffix = null
    ): string
    {
        $key = sprintf(
            'dashboard.%s.%s_%s',
            $prefix,
            $dateRange['start']->format('Y-m-d'),
            $dateRange['end']->format('Y-m-d')
        );

        if ($suffix) {
            $key .= '.' . $suffix;
        }

        return $key;
    }

    /**
     * Formata valor em centavos para moeda BRL.
     *
     * @param int $cents
     * @return string
     */
    private function formatCurrency(int $cents): string
    {
        return 'R$ ' . number_format(abs($cents) / 100, 2, ',', '.');
    }

    /**
     * Gera script de configuração para gráfico Donut/Pie.
     *
     * @param string $title
     * @param array $options
     * @return RawJs
     */
    public function getChartScript(
        string $title,
        array  $options = []
    ): RawJs
    {
        $defaultOptions = [
            'showLegend'     => true,
            'showTitle'      => true,
            'legendPosition' => 'top',
            'legendAlign'    => 'left',
            'showGrid'       => false,
            'showTicks'      => false,
            'currency'       => 'BRL',
            'locale'         => 'pt-BR',
        ];

        $config = array_merge($defaultOptions, $options);

        return $this->buildChartConfig($title, $config);
    }

    /**
     * Constrói a configuração do gráfico.
     *
     * @param string $title
     * @param array $config
     * @return RawJs
     */
    private function buildChartConfig(string $title, array $config): RawJs
    {
        $gridDisplay = $config['showGrid'] ? 'true' : 'false';
        $ticksDisplay = $config['showTicks'] ? 'true' : 'false';
        $legendDisplay = $config['showLegend'] ? 'true' : 'false';
        $titleDisplay = $config['showTitle'] ? 'true' : 'false';

        return RawJs::make(<<<JS
{
    scales: {
        y: {
            grid: { display: {$gridDisplay} },
            ticks: { display: {$ticksDisplay} }
        },
        x: {
            grid: { display: {$gridDisplay} },
            ticks: { display: {$ticksDisplay} }
        }
    },
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        title: {
            display: {$titleDisplay},
            text: '{$title}',
            padding: {
                top: 10,
                bottom: 30
            },
            font: {
                size: 16,
                weight: 'bold'
            }
        },
        legend: {
            display: {$legendDisplay},
            position: '{$config['legendPosition']}',
            align: '{$config['legendAlign']}',
            labels: {
                usePointStyle: true,
                padding: 15
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || context.label || '';
                    let value = context.parsed.y ?? context.parsed.x ?? context.parsed;

                    if (value === null || value === undefined) {
                        return label;
                    }

                    // Formata o valor em moeda
                    const valueFormatted = new Intl.NumberFormat('{$config['locale']}', {
                        style: 'currency',
                        currency: '{$config['currency']}',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(Math.abs(value) / 100);

                    // Calcula a porcentagem
                    const data = context.dataset.data;
                    const total = data.reduce((acc, val) => acc + Math.abs(val || 0), 0);
                    const percent = total > 0 ? ((Math.abs(value) / total) * 100).toFixed(2) : '0.00';

                    return label + ': ' + valueFormatted + ' (' + percent + '%)';
                }
            }
        }
    }
}
JS
        );
    }

    /**
     * Gera script para gráfico de linha/barra.
     *
     * @param string $title
     * @param array $options
     * @return RawJs
     */
    public function getLineChartScript(
        string $title,
        array  $options = []
    ): RawJs
    {
        $defaultOptions = [
            'showLegend' => true,
            'showTitle'  => true,
            'showGrid'   => true,
            'showTicks'  => true,
            'currency'   => 'BRL',
            'locale'     => 'pt-BR',
            'tension'    => 0.4,
            'fill'       => false,
        ];

        $config = array_merge($defaultOptions, $options);

        return $this->buildLineChartConfig($title, $config);
    }

    /**
     * Constrói configuração para gráfico de linha.
     *
     * @param string $title
     * @param array $config
     * @return RawJs
     */
    private function buildLineChartConfig(string $title, array $config): RawJs
    {
        $gridDisplay = $config['showGrid'] ? 'true' : 'false';
        $ticksDisplay = $config['showTicks'] ? 'true' : 'false';
        $legendDisplay = $config['showLegend'] ? 'true' : 'false';
        $titleDisplay = $config['showTitle'] ? 'true' : 'false';
        $fill = $config['fill'] ? 'true' : 'false';

        return RawJs::make(<<<JS
{
    scales: {
        y: {
            beginAtZero: true,
            grid: { display: {$gridDisplay} },
            ticks: {
                display: {$ticksDisplay},
                callback: function(value) {
                    return new Intl.NumberFormat('{$config['locale']}', {
                        style: 'currency',
                        currency: '{$config['currency']}',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(Math.abs(value) / 100);
                }
            }
        },
        x: {
            grid: { display: {$gridDisplay} },
            ticks: { display: {$ticksDisplay} }
        }
    },
    responsive: true,
    maintainAspectRatio: true,
    interaction: {
        mode: 'index',
        intersect: false
    },
    elements: {
        line: {
            tension: {$config['tension']},
            fill: {$fill}
        }
    },
    plugins: {
        title: {
            display: {$titleDisplay},
            text: '{$title}',
            padding: { top: 10, bottom: 30 },
            font: { size: 16, weight: 'bold' }
        },
        legend: {
            display: {$legendDisplay},
            position: 'top',
            align: 'end',
            labels: { usePointStyle: true, padding: 15 }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    let value = context.parsed.y;

                    if (value === null || value === undefined) {
                        return label;
                    }

                    const valueFormatted = new Intl.NumberFormat('{$config['locale']}', {
                        style: 'currency',
                        currency: '{$config['currency']}',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(Math.abs(value) / 100);

                    return label + ': ' + valueFormatted;
                }
            }
        }
    }
}
JS
        );
    }

    /**
     * Limpa o cache do dashboard.
     *
     * @param string|null $prefix
     * @return void
     */
    public function clearCache(?string $prefix = null): void
    {
        if ($prefix) {
            Cache::forget("dashboard.{$prefix}.*");
        } else {
            Cache::forget('dashboard.*');
        }

        Log::info('Cache do dashboard limpo.', ['prefix' => $prefix]);
    }

    /**
     * Obtém estatísticas resumidas do período.
     *
     * @param array $filters
     * @return array
     */
    public function getSummaryStats(array $filters, ?TransactionTypeEnum $type = null): array
    {
        $dateRange = $this->extractDateRange($filters);

        $installments = $this->fetchInstallmente($dateRange, $type);

        $balance = 0;
        if (!$type) {
            $expenses = $installments->filter(
                fn ($item) => $item->transaction->type === TransactionTypeEnum::EXPENSE
            );

            $income = $installments->filter(
                fn ($item) => $item->transaction->type === TransactionTypeEnum::INCOME
            );
            $balance = abs($income->sum('amount'));
            - abs($expenses->sum('amount'));
        }

        $total = abs($installments->sum('amount'));

        // Preparar dados históricos diários para gráficos
        $history = $this->prepareDailyHistory($installments, $dateRange);

        return [
            'total'           => $total,
            'total_formatted' => $this->formatCurrency($total),
            'count'           => $installments->count(),
            'period'          => [
                'start' => $dateRange['start']->format('d/m/Y'),
                'end'   => $dateRange['end']->format('d/m/Y'),
            ],
            // Dados para gráficos ApexCharts
            'history'         => $history['data'],
            'labels'          => $history['labels'],
            'balance' => $balance,
            'balance_formatted' => $this->formatCurrency($balance)
        ];
    }

    /**
     * Prepara histórico diário para gráficos.
     *
     * @param \Illuminate\Support\Collection $installments
     * @param array $dateRange
     * @return array
     */
    private function prepareDailyHistory($installments, array $dateRange): array
    {
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $dailyData = [];
        $labels = [];

        $current = $start->copy();
        while ($current->lte($end)) {
            $day = $current->day;
            $labels[] = $day;

            $dayTotal = $installments
                ->filter(fn ($item) => $item->due_date->day === $day)
                ->sum('amount');

            $dailyData[] = abs($dayTotal) / 100; // Converter para reais

            $current->addDay();
        }

        return [
            'data'   => $dailyData,
            'labels' => $labels,
        ];
    }

    public function getScriptDonutPie(?int   $totalItems, string $title, string $subtitle, array $series, array $labels,
                                      string $type = 'donut', array $color = [], array $data = []): array
    {

        $color = $color ?? $this->getColors();
        $subtitle = $totalItems ? 'Total de' . MaskHelper::covertIntToReal($totalItems) : '';

        $height = 350;
        $count = count($data);
        if ($count > 3) {
            $height = $height + (floor($count / 4) * 50);
        }

        return [
            'chart'      => [
                'type'   => $type,
                'height' => $height,
            ],
            'series'     => $series,
            'labels'     => $labels,
            'colors'     => $color,
            'legend'     => [
                'show'                => true,
                'showForSingleSeries' => true,
                'position'            => 'bottom',
                'horizontalAlign'     => 'left',
                'fontSize'            => '12px',
                'markers'             => [
                    'width'  => 12,
                    'height' => 12,
                ],
                'itemMargin'          => [
                    'horizontal' => 8,
                    'vertical'   => 4,
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            //            'plotOptions' => [
            //                'pie' => [
            //                    'donut' => [
            //                        'size'   => '65%',
            //                        'labels' => [
            //                            'show'  => true,
            //                            'name'  => [
            //                                'show'     => true,
            //                                'fontSize' => '14px',
            //                            ],
            //                            'value' => [
            //                                'show'       => true,
            //                                'fontSize'   => '20px',
            //                                'fontWeight' => 'bold',
            //                            ],
            //                        ],
            //                    ],
            //                ],
            //            ],
            'tooltip'    => [
                'enabled' => true,
            ],

            'title'    => [
                'text'  => $title,
                'style' => [
                    'fontSize'   => '20px',
                    'fontWeight' => 'bold'
                ],
            ],
            'subtitle' => [
                'text' => $subtitle,
            ],
        ];
    }

    public function getScriptBar(?int   $totalItems, string $title, string $subtitle, array $series,
                                 string $type = 'bar', array $color = [], array $data = []): array
    {

        $color = $color ?? $this->getColors();
        $subtitle = $totalItems ? 'Total de' . MaskHelper::covertIntToReal($totalItems) : '';

        $height = 350;
        $count = count($data);
        if ($count > 3) {
            $height = $height + (floor($count / 4) * 50);
        }

        return [
            'chart'      => [
                'type'   => $type,
                'height' => $height,
            ],
            'series'     => $series,
            'colors'     => $color,
            'xaxis'      => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis'      => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'legend'     => [
                'show'                => true,
                'showForSingleSeries' => true,
                'position'            => 'bottom',
                'horizontalAlign'     => 'left',
                'fontSize'            => '12px',
                'markers'             => [
                    'width'  => 12,
                    'height' => 12,
                ],
                'itemMargin'          => [
                    'horizontal' => 8,
                    'vertical'   => 4,
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'tooltip'    => [
                'enabled' => true,
            ],

            'title'    => [
                'text'  => $title,
                'style' => [
                    'fontSize'   => '20px',
                    'fontWeight' => 'bold'
                ],
            ],
            'subtitle' => [
                'text' => $subtitle,
            ],
        ];
    }

    public function getExtraScriptStats(): RawJs
    {
        return RawJs::make(<<<'JS'
    {
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '14px'
                        },
                        value: {
                            show: true,
                            fontSize: '20px',
                            fontWeight: 'bold',
                            formatter: function(w) {
                                return new Intl.NumberFormat('pt-BR', {
                                  style: 'currency',
                                  currency: 'BRL'
                                }).format(w);
                              }
                        }
                    },
                },
            },
        },
        tooltip: {
            x: {
                formatter: function (val) {
                    return 'Dia ' + val
                }
            },
             y: {
                formatter: function (val, opt) {
                                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(val)
                },
            }
        }
    }
    JS
        );
    }
}
