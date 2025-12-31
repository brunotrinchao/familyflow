<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\TransactionStatusEnum;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Widgets\InvoiceNavigationWidget;
use Carbon\Carbon;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): HtmlString
    {
        $date = Carbon::parse($this->record->period_date);
        $monthYearLabel = Str::ucfirst($date->translatedFormat('F Y'));


        return new HtmlString("Fatura {$monthYearLabel}");
    }

    public function getSubheading(): string|Htmlable|null
    {
        $now = Carbon::now()->firstOfMonth();
        $date = Carbon::parse($this->record->period_date);

        $color = Color::Purple;


        $iconName = $this->record->status->getIcon();
        $iconColor =  $this->record->status->getColor();
        $actualLabel = $this->record->status->getLabel();
        if ($iconName) {
            $iconName = $iconName->getIconForSize(IconSize::Medium); // ou o método que retorna a string 'heroicon-o-lock-open'
            $color = $this->getColor()[$iconColor];
        }

        $brandName = $this->record->creditCard->name ?? 'Desconhecido';

        $imageUrl = asset('storage/' . $this->record->creditCard->brand->icon_path);

        $sourceHtml = view('components.source-icon-view', [
            'image' => $imageUrl,
            'brand' => $brandName,
            'source' => null
        ])->render();

        return new HtmlString("
        <div class='flex items-center flex-wrap gap-3'>
            {$sourceHtml}
            <div class='inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold' style='background-color: {$color[200]}; color: {$color[500]}'>
                " . Blade::render("<x-filament::icon icon='{$iconName}' class='h-4 w-4' />") . "
                <span>{$actualLabel}</span>
            </div>
        </div>
    ");
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceNavigationWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('export')
                ->hiddenLabel()
                ->link()
                ->tooltip('Exportar fatura')
                ->color(Color::hex('#ffffff')[100])
                ->icon(Iconoir::Download),
            Action::make('export')
                ->hiddenLabel()
                ->link()
                ->tooltip('Imprimir fatura')
                ->color(Color::hex('#ffffff')[100])
                ->icon(Iconoir::PrintingPage)
        ];
    }

    protected function getListeners(): array
    {
        return [
            'refresh-page' => '$refresh',
        ];
    }

    private function getColor(){
        return [
                'primary' => Color::Blue,
                'danger'  => Color::Red,
                'gray'    => Color::Gray,
                'info'    => Color::Sky,
                'success' => Color::Green,
                'warning' => Color::Yellow,
                'stone'   => Color::Stone,
                'purple'  => Color::Purple,
            ];
    }
}
