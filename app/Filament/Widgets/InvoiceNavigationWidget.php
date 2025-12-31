<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\Widget;

class InvoiceNavigationWidget extends Widget
{
    protected string $view = 'filament.widgets.invoice-navigation-widget';

    protected int|string|array $columnSpan = 'full';

    // A propriedade $record será injetada automaticamente pela página ViewInvoice
    public ?Invoice $record = null;

    protected function getNavigationData(): array
    {
        if (!$this->record) {
            return [
                'previous' => null,
                'next'     => null
            ];
        }

        $tenant = Filament::getTenant();
        $familyId = $tenant?->id;
        $familySlug = $tenant?->slug;

        // --- Lógica de Navegação ---

        // 1. Fatura Anterior (Immediately Previous)
        $previousInvoice = Invoice::query()
            ->where('family_id', $familyId)
            // Filtra faturas cuja data de período seja ANTERIOR à atual
            ->whereDate('period_date', '<', $this->record->period_date)
            ->where('credit_card_id', $this->record->creditCard->id)
            ->orderBy('period_date', 'desc') // Mais recente primeiro
            ->limit(1)
            ->first();

        // 2. Próxima Fatura (Immediately Next)
        $nextInvoice = Invoice::query()
            ->where('family_id', $familyId)
            // Filtra faturas cuja data de período seja POSTERIOR à atual
            ->whereDate('period_date', '>', $this->record->period_date)
            ->where('credit_card_id', $this->record->creditCard->id)
            ->orderBy('period_date', 'asc') // Mais antigo primeiro
            ->limit(1)
            ->first();

        // --- Geração de Links e Rótulos ---

        $actual = $this->record->period_date->translatedFormat('F/Y');

        $previous = null;
        if ($previousInvoice) {
            $previous = [
                'label' => $previousInvoice->period_date->translatedFormat('F Y'),
                'url'   => route('filament.admin.resources.invoices.view', [
                    'tenant' => $familySlug,
                    // 🚨 INCLUIR O TENANT 🚨
                    'record' => $previousInvoice->id
                ]),
            ];
        }

        $next = null;
        if ($nextInvoice) {
            $next = [
                'label' => $nextInvoice->period_date->translatedFormat('F Y'),
                'url'   => route('filament.admin.resources.invoices.view', [
                    'tenant' => $familySlug,
                    // 🚨 INCLUIR O TENANT 🚨
                    'record' => $nextInvoice->id
                ]),
            ];
        }

        return [
            'previous' => $previous,
            'next'     => $next,
            'actual'   => $actual
        ];
    }
}
