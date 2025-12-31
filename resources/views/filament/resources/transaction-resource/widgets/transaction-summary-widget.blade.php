<x-filament-widgets::widget>
    @php
        // Acesso aos dados calculados do Widget
        $data = $this->getSummaryData();

        // Define helpers locais para formatação e cor
        $format = fn($v) => \App\Helpers\MaskHelper::covertIntToReal($v);
        $color = fn($v, $isExpense = false) => match (true) {
            // Se for despesa (valor sempre positivo, mas representa débito)
            $isExpense => 'text-danger-600 dark:text-danger-400',
            // Saldo Positivo
            $v > 0 => 'text-success-600 dark:text-success-400',
            // Saldo Negativo/Zero
            default => 'text-primary-600 dark:text-primary-400',
        };

        // Despesa (valores são negativos na contabilidade, mas a soma em $data é positiva)
        $despesaRealizada = $data['despesa_realizada'];
        $despesaPrevista = $data['despesa_prevista'];
    @endphp

    <div class="fi-wi-card p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-2 gap-x-4 text-sm">

            {{-- Linha 1: Saldo Anterior --}}
            <div class="text-gray-500 dark:text-gray-400">saldo anterior</div>
            <div class="text-right font-medium text-gray-700 dark:text-gray-200">
                {{ $format($data['saldo_anterior']) }}
            </div>

            {{-- Separador fino --}}
            <div class="col-span-2 my-1 border-b border-gray-100 dark:border-gray-700"></div>

            {{-- Linha 2: Receita Realizada (Verde) --}}
            <div class="text-gray-500 dark:text-gray-400">receita realizada</div>
            <div class="text-right text-success-600 font-medium">
                {{ $format($data['receita_realizada']) }}
            </div>

            {{-- Linha 3: Receita Prevista (Verde Claro) --}}
            <div class="text-gray-500 dark:text-gray-400">receita prevista</div>
            <div class="text-right text-success-400 font-medium">
                {{ $format($data['receita_prevista']) }}
            </div>

            {{-- Linha 4: Despesa Realizada (Vermelho) --}}
            <div class="text-gray-500 dark:text-gray-400">despesa realizada</div>
            <div class="text-right {{ $color($despesaRealizada, true) }} font-medium">
                {{ $format($despesaRealizada * -1) }} {{-- Exibe como valor negativo visível --}}
            </div>

            {{-- Linha 5: Despesa Prevista (Vermelho Claro) --}}
            <div class="text-gray-500 dark:text-gray-400">despesa prevista</div>
            <div class="text-right text-danger-400 font-medium">
                {{ $format($despesaPrevista * -1) }} {{-- Exibe como valor negativo visível --}}
            </div>

            {{-- Separador Grosso (Linha de Saldo) --}}
            <div class="col-span-2 my-1 border-b border-gray-400 dark:border-gray-600"></div>

            {{-- Linha 6: SALDO REAL (Destaque Azul/Negativo) --}}
            <div class="font-bold text-gray-700 dark:text-gray-100">saldo</div>
            <div class="text-right font-bold {{ $color($data['saldo_real']) }} text-lg">
                {{ $format($data['saldo_real']) }}
            </div>

            {{-- Linha 7: PREVISÃO TOTAL (Destaque) --}}
            <div class="font-medium text-gray-500 dark:text-gray-400">previsto</div>
            <div class="text-right font-bold text-lg {{ $color($data['saldo_previsto']) }}">
                {{ $format($data['saldo_previsto']) }}
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
