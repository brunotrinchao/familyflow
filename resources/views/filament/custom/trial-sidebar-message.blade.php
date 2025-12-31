@php
    use Filament\Facades\Filament;
    use Carbon\Carbon;

    // --- 1. Inicialização e Obtenção do Tenant ---
    $family = Filament::getTenant();

    $showTrialMessage = false;
    $daysRemaining = 0;
    $percentageUsed = 0;

    // Obtém a data de fim do trial da assinatura (prioritário)
    $trialEndDate = $family?->getTrialEndDate();

    // A data de criação é necessária para a barra de progresso
    $trialStartDate = $family?->created_at?->startOfDay();

    // Se não há Tenant, ou não há data de fim de trial, ou não há data de início, sair.
    if (!$family || !$trialEndDate || !$trialStartDate) {
        return;
    }

    // --- 2. Normalização e Verificação de Expiração ---
    $today = Carbon::today();
    $trialEndDate = $trialEndDate->startOfDay(); // Garantir que é 00:00:00

    // Se o Trial já expirou, não mostra nada.
    if ($today->greaterThan($trialEndDate)) {
        return;
    }

    // --- 3. CÁLCULO CENTRAL ---

    // Dias restantes (arredondado para cima, incluindo o dia de hoje)
    $daysRemaining = $today->diffInDays($trialEndDate);

    // Duração total do trial (Dias de diferença entre o início e o fim)
    $totalDurationDays = $trialStartDate->diffInDays($trialEndDate);

    // Dias decorridos (diferença entre o início e hoje)
    $elapsedDays = $trialStartDate->diffInDays($today);

    // Marca para exibição
    $showTrialMessage = true;

    // --- 4. CÁLCULO DA BARRA DE PROGRESSO ---
    if ($totalDurationDays > 0) {
        // Porcentagem utilizada em relação à duração total
        $percentageUsed = min(100, ($elapsedDays / $totalDurationDays) * 100);
    }

@endphp

@if ($showTrialMessage)
    <div
        id="header-trial-bar"
        role="alert"
    >
        <div>
            <h4>
                Teste grátis
            </h4>
            <p class="">
                @if ($daysRemaining > 1)
                    {{ $daysRemaining }} dias restantes
                @else
                    Último dia
                @endif
            </p>
            {{-- Mensagem de Dias Restantes --}}


            <div class="progress-container">
                <div class="progress-bar" style="width: {{ round(100 - $percentageUsed, 1) }}%;"></div>
            </div>

        </div>
    </div>


    <style>
        #header-trial-bar {
            display: flex;
            background-color: #ededed;
            align-items: center;
            justify-content: center;
            height: 60px;
            width: 100%;
            border-bottom: 1px solid #a1a1aa4d;
        }

        #header-trial-bar > div {
            /*max-width: 1059px;*/
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin: 0 20px;
            gap: 25px;
        }


        #header-trial-bar > div > h4 {
            display: flex;
            flex-direction: column;
            color: #24282E;
            flex-shrink: 0;
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
            gap: 3px;
            align-items: center;
            justify-content: center;
        }

        #header-trial-bar > div > p {
            flex-shrink: 0;
            color: #454843;
            font-size: 14px;
            font-weight: 400;
            line-height: 135%;
        }


        #header-trial-bar .progress-container {
            width: 100%;
            height: 10px;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden; /* Ensures the progress bar stays within the container */
        }

        #header-trial-bar .progress-container .progress-bar {
            height: 100%;
            background-color: color-mix(in oklab, var(--primary-400) 75%, transparent); /* Blue color for progress */
            border-radius: 5px;
            transition: width 0.5s ease-in-out; /* Smooth transition for width changes */
        }
    </style>
@endif
