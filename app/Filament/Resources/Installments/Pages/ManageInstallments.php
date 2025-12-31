<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Filament\Resources\Installments\InstallmentResource;
use App\Models\Installment;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ManageInstallments extends ManageRecords
{
    use HasFiltersForm;
    use ExposesTableToWidgets;
    use HasToggleableTable;

    protected static string $resource = InstallmentResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Action::make('quest')
                ->modal()
                ->color(Color::Green)
                ->size(Size::ExtraSmall)
                ->link()
                ->label('Entenda seu saldo')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(function (): HtmlString {
                    return new HtmlString('
                <div style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 800px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div style="background-color: #f8fafc; padding: 20px; border-bottom: 1px solid #e2e8f0;">
        <h2 style="margin: 0; color: #1e293b;">Guia do Resumo Financeiro</h2>
        <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Entenda como calculamos a saúde do seu bolso neste mês.</p>
    </div>

    <div style="padding: 20px;">
        <h3 style="color: #0f172a; border-left: 4px solid #10b981; padding-left: 10px;">Fluxo Realizado (O que já aconteceu)</h3>
        <p>Representa o dinheiro que efetivamente já entrou ou saiu das suas contas bancárias.</p>
        <ul style="list-style-type: none; padding: 0;">
            <li style="margin-bottom: 10px;"><strong>✅ Receita Realizada:</strong> Salários, PIX recebidos ou rendimentos que já foram marcados como <strong>Pagos</strong>.</li>
            <li style="margin-bottom: 10px;"><strong>❌ Despesa Realizada:</strong> Contas, boletos ou compras no débito que já foram marcados como <strong>Pagos</strong>.</li>
        </ul>

        <h3 style="color: #0f172a; border-left: 4px solid #f59e0b; padding-left: 10px; margin-top: 30px;">Fluxo Previsto (O que está por vir)</h3>
        <p>São os lançamentos que possuem data para este mês, mas ainda não foram finalizados.</p>
        <ul style="list-style-type: none; padding: 0;">
            <li style="margin-bottom: 10px;"><strong>⏳ Receita Prevista:</strong> Dinheiro que você espera receber até o fim do mês (ex: vendas a prazo). Marcados como <strong>Pendente</strong></li>
            <li style="margin-bottom: 10px;"><strong>📑 Despesa Prevista:</strong> Contas a pagar, faturas de cartão de crédito em aberto ou lançamentos agendados.  Marcados como <strong>Pendente</strong></li>
        </ul>

        <h3 style="color: #0f172a; border-left: 4px solid #3b82f6; padding-left: 10px; margin-top: 30px;">Saúde Financeira Global</h3>
        <div style="background-color: #eff6ff; padding: 15px; border-radius: 6px;">
            <p style="margin-top: 0;"><strong>💰 Saldo em Contas:</strong> É a soma do saldo atual de todas as suas contas bancárias e carteiras cadastradas. É o seu "dinheiro vivo" agora.</p>
            <p style="margin-bottom: 0;"><strong>🚀 Projeção Final:</strong> O cálculo mais importante. Ele diz: <em>"Se eu pagar tudo que devo e receber tudo que me devem hoje, quanto terei no banco ao final do mês?"</em></p>
        </div>
    </div>

    <div style="background-color: #f1f5f9; padding: 15px; font-size: 12px; color: #475569; text-align: center;">
        Nota: As despesas são tratadas como valores negativos para fins de cálculo de projeção.
    </div>
</div>
                ');
                }),
        ];
    }

    protected function getListeners(): array
    {
        return [
            'refresh-page' => '$refresh',
        ];
    }


    protected function getHeaderWidgets(): array
    {
        return [
        ];
    }

    public function getTableRecords(): \Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection
    {
        // 1. Obter e resolver os dados do filtro
        $activeFilters = $this->tableFilters;
        $filters = $activeFilters['filter'] ?? []; // Agora pega os valores do filtro

        // Extrai os valores das chaves do filtro
        $dates = $filters['invoice_date'] ?? null;
        $category = $filters['category'] ?? null;
        $type = $filters['type'] ?? null;
        $source = $filters['source'] ?? null;

        // Resolve datas (como o filtro não está aplicando o where, fazemos aqui)
        $date = Carbon::parse($dates ?? Carbon::now()->startOfMonth());
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        $monthYearLabel = $date->translatedFormat('F Y');
        $familyId = Filament::getTenant()?->id;

        $invoiceQuery = Invoice::query()
            ->where('family_id', $familyId)
            ->whereBetween('period_date', [
                $startDate,
                $endDate
            ])
            ->with(['creditCard']);

        // 🚨 Aplica os filtros na query de Installments aninhada à Invoice 🚨
        $invoiceQuery->whereHas('installments', function (Builder $qInstallment) use ($type, $category, $source) {
            $qInstallment->whereHas('transaction', function (Builder $qTransaction) use ($type, $category, $source) {
                // Aplicação Condicional
                $qTransaction->when($type, fn ($q, $v) => $q->where('type', $v));
                $qTransaction->when($category, fn ($q, $v) => $q->where('category_id', $v));
                $qTransaction->when($source, fn ($q, $v) => $q->where('source', $v));
            });
        });
        $invoices = $invoiceQuery->get()->map(function (Invoice $invoice) use ($monthYearLabel) {
            $cardName = $invoice->creditCard->name ?? 'Cartão Desconhecido';

            $transaction = $invoice->installments->first()->transaction;

            // Propriedade injetada: Descrição da Fatura
            $invoice->title = "Fatura {$cardName} ({$monthYearLabel})";
            $invoice->description = null;
            $invoice->amount = $invoice->total_amount;
            $invoice->source = $invoice->creditCard->brand;
            $invoice->category = null;
            $invoice->due_date = Carbon::parse($invoice->period_date)->day($invoice->creditCard->due_day);
            $invoice->type = $transaction->type;
            // Propriedade injetada: Flag de identificação
            $invoice->paymentSource = $transaction->source;
            $invoice->is_invoice = true;
            $invoice->load('creditCard');

            return $invoice;
        });

        $installmentQuery = Installment::query()
            ->where('family_id', $familyId)
            ->whereNull('invoice_id')
            ->whereNotNull('account_id')
            ->whereBetween('due_date', [
                $startDate,
                $endDate
            ]) // Coluna CORRETA: due_date
            ->with([
                'transaction',
                'account'
            ]);

        // 🚨 Aplica os mesmos filtros na query de Installments 🚨
        $installmentQuery->whereHas('transaction', function (Builder $qTransaction) use ($type, $category, $source) {
            // Aplicação Condicional
            $qTransaction->when($type, fn ($q, $v) => $q->where('type', $v));
            $qTransaction->when($category, fn ($q, $v) => $q->where('category_id', $v));
            $qTransaction->when($source, fn ($q, $v) => $q->where('source', $v));
        });
        $installments = $installmentQuery->get()->map(function (Installment $installment) {
            $transaction = $installment->transaction;
            $desc = $transaction->title ?? 'Sem descrição';
            $installNum = $installment->number;
            // Formata a descrição: Descrição + (Parcela X), se houver
            $installmentText = ($installNum > 1) ? " (Parc. {$installNum})" : '';

            $installment->title = $desc . $installmentText;
            $installment->description = $transaction->description;
            $installment->source = $installment->account->brand;
            $installment->category = $transaction->category;
            $installment->type = $transaction->type;
            $installment->paymentSource = $transaction->source;
            $installment->is_invoice = false;
            return $installment;
        });

        // 4. Combinação e Ordenação
        $finalRecords = $invoices->toBase()
            ->merge($installments->toBase())
            ->sortBy(fn ($record) => $record->due_date) // Ordena por data
            ->values();

        return $finalRecords;

    }

}
