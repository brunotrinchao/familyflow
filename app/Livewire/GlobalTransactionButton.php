<?php

namespace App\Livewire;

use App\Filament\Resources\Transactions\Schemas\TransactionFormChoice;
use App\Models\Category;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Livewire\Component;

class GlobalTransactionButton extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function getFormSchema(): array
    {
        return [];
    }

    protected function getGlobalActions(): array
    {
        return [
            // Criamos o grupo para envolver a ação principal
            ActionGroup::make([
                $this->createTransactionAction(),
                // Aqui você adicionaria outras ações futuras, como Importar Extrato
            ])
                ->button() // Renderiza o grupo como um botão único
                ->label(__('custom.title.launch')) // O rótulo é aplicado ao botão do grupo
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->size(Size::ExtraLarge)
        ];
    }

    public function createTransactionAction(): Action
    {
        return CreateAction::make()
            ->model(Category::class)
            ->modalIcon(Iconoir::Plus)
            ->modalHeading(__('custom.title.launch'))
            ->label(__('custom.title.launch'))
            ->icon(Iconoir::PlusCircle)
            ->color('primary')
            ->button() // Faz parecer um botão, não um link
            ->size(Size::ExtraLarge) // Tamanho adequado para a Topbar
            ->modal()
            ->modalWidth(Width::Medium)
            ->modalSubmitAction(false) // Removes the default submit button
            ->modalCancelAction(false) // Removes the default cancel button
            ->createAnother(false)
            ->schema(fn (Schema $schema) => TransactionFormChoice::configure($schema));
    }

    public function getLabel(): string
    {
        return __('custom.title.launch');
    }

    public function getIcon()
    {
        return Iconoir::PlusCircle;
    }

    public function render()
    {
        return view('livewire.global-transaction-button');
    }
}
