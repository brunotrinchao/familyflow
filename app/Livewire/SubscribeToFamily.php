<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SubscribeToFamily extends Component
{
    public $name;         // Nome no cartão
    public $stripeIntent; // Setup Intent do Stripe

    // ID do plano Stripe (ex: Premium Mensal)
    protected string $priceId;

    public function mount()
    {
        $this->priceId = env('ID_PLAN');
        $family = Filament::getTenant();

        // 1. CHAVE: Cria o Setup Intent para garantir o processamento seguro do cartão
        if ($family) {
            $this->stripeIntent = $family->createSetupIntent();
        } else {
            // Se não houver Tenant, redirecionar ou mostrar erro
            abort(403, 'Acesso negado: Contexto da família não encontrado.');
        }
    }

    public function subscribe()
    {
        $family = Filament::getTenant();

        try {
            $trialDays = (int)ENV('TRIAL_DAYS');
            // 2. Anexa o método de pagamento e inicia a nova assinatura
            $family->newSubscription('default', $this->priceId)
                ->withOptions([
                    'locale' => 'pt-BR',
                    // Define o idioma para Português do Brasil
                ])
                ->trialDays($trialDays)
                ->create($this->paymentMethodId, [
                    'email' => $family->email
                    // Opcional: E-mail de faturamento
                ]);

            // 3. Atualiza o status do Tenant e redireciona
            $family->update(['status' => \App\Enums\FamilyStatusEnum::ACTIVE]);

            Filament::notify('success', 'Assinatura ativada com sucesso!');
            return redirect()->to(Filament::getUrl()); // Redireciona para o Dashboard

        } catch (\Exception $e) {
            Log::error('Stripe Subscription Failed', ['message' => $e->getMessage()]);
            // Exibe o erro para o usuário (necessário definir $paymentMethodId no frontend)
            session()->flash('error', 'Falha no pagamento: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.subscribe-to-family');
    }
}
