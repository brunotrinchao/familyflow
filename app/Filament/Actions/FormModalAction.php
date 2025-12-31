<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Closure;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Ação personalizada para abrir um modal que aceita um formulário (schema) dinâmico.
 * * Uso: FormModalAction::make('criarCategoria')->schema([...])->action(fn ($data) => ...);
 */
class FormModalAction
{
    /**
     * Retorna uma nova instância da Action, configurada para ser um modal com formulário.
     *
     * @param string $name O nome interno da ação.
     * @return Action
     */
    public static function make(string $name): Action
    {
        return Action::make($name)
            ->modalSubmitActionLabel('Salvar')
            ->modalCancelActionLabel('Cancelar')
            ->icon('heroicon-o-pencil-square') // Ícone padrão
            ->color('primary'); // Cor padrão

        // O formulário (schema) será definido externamente
        //            ->schema($schema);
    }


    /**
     * Método chainable para definir o schema (formulário) da ação.
     *
     * @param Action $action A ação atual.
     * @param array|Closure $schema A array de campos ou closure que retorna a array de campos.
     * @return Action
     */
    public static function schema(Action $action, array|Closure $schema): Action
    {
        // Define o schema do formulário no próprio objeto Action,
        // que é lido pela closure `form()` definida em `make()`.
        return $action->schema($schema);
    }

    /**
     * Combinação do make e do schema para uso mais fluente.
     *
     * @param string $name
     * @param array|Closure $schema
     * @return Action
     */
    public static function create(string $name, array|Closure $schema): Action
    {
        return static::make($name)
            ->schema($schema);
    }
}
