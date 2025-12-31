<div class="flex items-center ml-3" >
    {{-- 1. Botão Manual --}}
    <x-filament::button
        color="primary"
        icon="iconoir-plus-circle"
        {{-- O segredo está aqui: chamamos mountAction com o nome definido no PHP --}}
        wire:click="mountAction('createTransaction')"
    >
       {{ $this->getLabel() }}
    </x-filament::button>

    {{-- 2. Container de Modais (OBRIGATÓRIO) --}}
    <x-filament-actions::modals />
</div>
