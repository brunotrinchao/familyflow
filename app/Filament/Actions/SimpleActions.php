<?php

namespace App\Filament\Actions;

use App\FormInterface;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class SimpleActions
{
    /**
     * Retorna uma Ação de Edição genérica, esperando que o schema e a lógica sejam fornecidos.
     *
     * @param callable $schemaCallback Retorna o array de componentes do formulário.
     * @param callable $actionCallback Lógica de execução da ação de edição.
     */
    public static function getViewEditAction(Width    $width,
                                             callable $schemaCallback,
                                             callable $actionCallback,
                                             string   $model = null,
                                             string   $recordName = 'Registro',
                                             bool     $modal = true): EditAction
    {
        $view = EditAction::make('edit-modal')
            ->modalHeading("Editar {$recordName}")
            ->modalWidth($width)
            ->modalIcon(Heroicon::PencilSquare)
            ->color('primary')
            // Preenche o formulário com os atributos do registro
//            ->fillForm(fn (Model $record) => $record->toArray())
            // Usa o schema fornecido pela closure
            ->schema($schemaCallback)
            // Usa a lógica de ação fornecida pela closure
            ->action($actionCallback);

        if ($modal) {
            $view->modal();
        } else {
            $view->slideOver();
        }

        if ($model) {
            $view->model($model);
        }

        return $view;
    }

    /**
     * Retorna uma Ação de Visualização (View) que contém as ações Edit e Delete no rodapé.
     *
     * @param callable $schemaCallback O schema de visualização/edição.
     * @param callable $actionCallback Lógica de execução da ação de edição.
     * @param string $recordName Nome do registro para o título do Delete.
     */
    public static function getViewWithEditAndDelete(
        Width     $width,
        callable  $schemaCallback,
        callable  $actionCallback,
        string    $model = null,
        callable  $schemaViewCallback = null,
        string    $recordName = 'Registro',
        ?callable $recordAction = null,
        bool      $modal = true
    ): ViewAction
    {
        $view = ViewAction::make('edit-modal')
            ->modalHeading("{$recordName}")
            ->modalWidth($width)
            ->modalIcon(Heroicon::InformationCircle)
            ->color('primary')
            ->schema(($schemaViewCallback ?? $schemaCallback))
            ->extraAttributes(['style' => 'display:none;'])
            ->modalFooterActions(function (Model $record) use (
                $actionCallback, $recordAction, $recordName, $width, $schemaCallback, $modal, $model
            ) {
                $isEditable = true;
                if ($recordAction) {
                    $isEditable = call_user_func($recordAction, $record);
                }

                $actions = [];

                if ($isEditable) {
                    // Ação de Edição (Se for um item específico da família)
                    $actions[] = self::getViewEditAction(
                        width         : $width,
                        schemaCallback: $schemaCallback,
                        actionCallback: $actionCallback,
                        model         : $model,
                        recordName    : $recordName,
                        modal         : $modal);

                    // Ação de Exclusão (Se for um item específico da família)
                    $actions[] = DeleteAction::make()
                        ->modalHeading(fn (Model $record) => "Excluir {$recordName}");
                }

                return $actions;
            });

        if ($modal) {
            $view->modal();
        } else {
            $view->slideOver();
        }

        if ($model) {
            $view->model($model);
        }

        return $view;
    }
}
