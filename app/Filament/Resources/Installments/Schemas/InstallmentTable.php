<?php

namespace App\Filament\Resources\Installments\Schemas;

use App\Enums\CategoryIconEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Installments\Utilities\InstallmentSummaries;
use App\Helpers\MaskHelper;
use App\Models\Category;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class InstallmentTable
{
    public static function configure(): array
    {
        return [
            TextColumn::make('due_date')
                ->label('')
                ->date('d')
                ->size(TextSize::Large)
                ->toggleable(false),
            ViewColumn::make('icon')
                ->label('')
                ->view('components.category-icon-view')
                ->viewData(function (mixed $record) {
                    $isInvoice = $record->is_invoice ?? false;
                    if ($isInvoice) {
                        $category = Category::where('icon', CategoryIconEnum::Notes)->first();
                    } else {
                        $category = $record->transaction->category;
                    }
                    return [
                        'data' => $category
                    ];
                }),
            TextColumn::make('title')
                ->label('')
                ->description(fn ($record) => $record->description ?? ''),
            TextColumn::make('source')
                ->label('')
                ->getStateUsing(function (mixed $record): HtmlString {
                    // 1. Transações Normais
                    if ($record->type !== TransactionTypeEnum::TRANSFER) {
                        return new HtmlString(view('components.source-icon-view', [
                            'image'  => asset('storage/' . ($record->source?->icon_path ?? '')),
                            'brand'  => $record->source?->name ?? 'Desconhecido',
                            'source' => $record->paymentSource,
                        ])->render());
                    }

                    $origin = $record->transaction?->account;
                    $destine = $record->transaction?->destinationAccount;

                    $isOut = $record->amount < 0;

                    $firstAccount = $origin;
                    $secondAccount = $destine;

                    return new HtmlString(view('components.transfer-source-icon-view', [
                        'origin'  => $firstAccount,
                        'destine' => $secondAccount,
                        'isOut'   => $isOut,
                        // Passamos isso para o componente decidir a cor da seta
                    ])->render());
                }),
            TextColumn::make('amount')
                ->label('')
                ->money('BRL')
                    ->color(fn (Model $record) => MaskHelper::amountColor($record->amount))
                ->getStateUsing(function (mixed $record): string {
                    return MaskHelper::covertIntToReal($record->amount);
                })
                ->summarize(InstallmentSummaries::getSummarizers()),
            TextColumn::make('status')
                ->label('')
                ->icon(false)
                ->badge()
        ];
    }
}
