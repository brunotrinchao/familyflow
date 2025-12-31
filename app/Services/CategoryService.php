<?php

namespace App\Services;

use App\Models\Category;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class CategoryService
{

    public static function create(array $data, ?Action $action): void
    {
        // 1. Validar unicidade
        if (Category::where('name', $data['name'])->exists()) {
            Notification::make('category_exist')
                ->title('Categoria já existente.')
                ->warning()
                ->send();

            $action->halt();
        }

        try {
            // 2. Criar o registro
            // Usar o método estático create() do Eloquent é mais direto.
            Category::create($data);

            // 3. Notificação de sucesso
//            Notification::make('createCategorySuccess')
//                ->title('Categoria cadastrada com sucesso!')
//                ->success()
//                ->send();

//            $action->success();

        } catch (Exception $e){
            // 4. Notificação de erro
            Notification::make('createCategoryError')
                ->title('Erro ao cadastrar!')
                ->body('Detalhe: ' . $e->getMessage())
                ->danger() // Usar danger/error para indicar falha
                ->send();

            $action->cancel();
        }
    }

    public static function update(array $data, Category $record, Action $action): void
    {
        // 1. Validar unicidade (ignorando o próprio registro)
        // Se a nova 'name' já existir em outro registro E não for o registro atual.
        if (Category::where('name', $data['name'])
            ->where('id', '!=', $record->id)
            ->exists())
        {
            Notification::make('category_exist')
                ->title('Categoria já existente.')
                ->warning()
                ->send();

            $action->halt();
        }

        try {
            // 2. Atualizar o registro
            // Chamar fill/update na instância $record.
            $record->fill($data)->save();

            // 3. Notificação de sucesso
//            Notification::make('updateCategorySuccess')
//                ->title('Categoria atualizada com sucesso!')
//                ->success()
//                ->send();
//
//            $action->success();

        } catch (Exception $e){
            // 4. Notificação de erro
            Notification::make('updateCategoryError')
                ->title('Erro ao atualizar!')
                ->body('Detalhe: ' . $e->getMessage())
                ->danger() // Usar danger/error para indicar falha
                ->send();

            $action->cancel();
        }
    }
}
