// resources/views/livewire/subscribe-to-family.blade.php

<div class="filament-card p-6">
    <h2 class="text-xl font-bold mb-4">Ativar Assinatura Premium</h2>

    @if (session()->has('error'))
        <div class="p-3 mb-4 bg-danger-500/10 text-danger-600 rounded-lg">{{ session('error') }}</div>
    @endif

    <form id="payment-form" wire:submit.prevent="subscribe">
        @csrf

        {{-- Campo oculto para o método de pagamento gerado pelo JS --}}
        <input type="hidden" id="payment-method" name="payment_method" wire:model="paymentMethodId">

        {{-- Nome no Cartão --}}
        <label for="card-holder-name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nome no Cartão</label>
        <input id="card-holder-name" type="text" wire:model="name" class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-primary-300">

        {{-- Elemento do Stripe: Onde o Stripe injeta os campos do cartão --}}
        <div id="card-element" class="mt-4 p-3 border rounded-md bg-white dark:bg-gray-700">
            </div>

        <button
            type="submit"
            id="card-button"
            class="mt-6 filament-button filament-button-size-md filament-button-color-primary"
            data-secret="{{ $stripeIntent->client_secret }}"
        >
            Assinar por R$ 49.99/mês
        </button>
        <p id="card-error" role="alert" class="text-sm text-danger-500 mt-2"></p>
    </form>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe("{{ env('STRIPE_KEY') }}");
    const elements = stripe.elements();
    const cardElement = elements.create('card');

    cardElement.mount('#card-element');

    const cardHolderName = document.getElementById('card-holder-name');
    const form = document.getElementById('payment-form');
    const clientSecret = document.getElementById('card-button').dataset.secret;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const { setupIntent, error } = await stripe.confirmCardSetup(
            clientSecret, {
                payment_method: {
                    card: cardElement,
                    billing_details: { name: cardHolderName.value }
                }
            }
        );

        if (error) {
            document.getElementById('card-error').textContent = error.message;
        } else {
            // Sucesso! Define o paymentMethodId no Livewire e submete
            @this.set('paymentMethodId', setupIntent.payment_method);
            @this.subscribe();
        }
    });
</script>
@endpush
