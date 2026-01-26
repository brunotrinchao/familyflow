<?php

namespace App\Services;

use App\Models\Category;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;
use App\Services\TenantContext;
use App\Enums\CategoryTypeEnum;

class CategoryService
{
    /**
     * Cria uma nova categoria.
     *
     * @param array $data
     * @param Action|null $action
     * @return Category
     * @throws Throwable
     */
    public function create(array $data, ?Action $action = null): Category
    {
        try {
            return DB::transaction(function () use ($data) {
                // Validar unicidade
                $exists = Category::where('name', $data['name'])
                    ->where('family_id', $data['family_id'] ?? null)
                    ->where('type', $data['type'])
                    ->exists();

                if ($exists) {
                    throw new Exception('Categoria já existe para esta família e tipo.');
                }

                $category = Category::create($data);
                $this->clearOptionsCache($category->family_id);

                return $category;
            });

        } catch (Throwable $e) {
            Log::error('Erro ao criar categoria.', [
                'message' => $e->getMessage(),
                'data'    => $data,
                'trace'   => $e->getTraceAsString(),
            ]);

            if ($action) {
                Notification::make('createCategoryError')
                    ->title('Erro ao cadastrar categoria!')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                $action->halt();
            }

            throw $e;
        }
    }

    /**
     * Atualiza uma categoria existente.
     *
     * @param array $data
     * @param Category $category
     * @param Action|null $action
     * @return Category
     * @throws Throwable
     */
    public function update(array $data, Category $category, ?Action $action = null): Category
    {
        try {
            return DB::transaction(function () use ($data, $category) {
                // Validar unicidade ignorando o próprio registro
                $exists = Category::where('name', $data['name'])
                    ->where('family_id', $data['family_id'] ?? $category->family_id)
                    ->where('type', $data['type'] ?? $category->type)
                    ->where('id', '!=', $category->id)
                    ->exists();

                if ($exists) {
                    throw new Exception('Categoria já existe para esta família e tipo.');
                }

                $category->update($data);
                $this->clearOptionsCache($category->family_id);

                return $category->fresh();
            });

        } catch (Throwable $e) {
            Log::error('Erro ao atualizar categoria.', [
                'message'     => $e->getMessage(),
                'category_id' => $category->id,
                'data'        => $data,
                'trace'       => $e->getTraceAsString(),
            ]);

            if ($action) {
                Notification::make('updateCategoryError')
                    ->title('Erro ao atualizar categoria!')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                $action->halt();
            }

            throw $e;
        }
    }

    /**
     * Deleta uma categoria (soft delete).
     *
     * @param Category $category
     * @return bool
     * @throws Throwable
     */
    public function delete(Category $category): bool
    {
        try {
            return DB::transaction(function () use ($category) {
                // Verificar se há transações usando esta categoria
                if ($category->transactions()->exists()) {
                    throw new Exception(
                        'Não é possível excluir categoria que possui transações vinculadas.'
                    );
                }

                $deleted = $category->delete();
                $this->clearOptionsCache($category->family_id);

                return $deleted;
            });

        } catch (Throwable $e) {
            Log::error('Erro ao deletar categoria.', [
                'message'     => $e->getMessage(),
                'category_id' => $category->id,
                'trace'       => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Restaura uma categoria deletada.
     *
     * @param int $categoryId
     * @return Category
     */
    public function restore(int $categoryId): Category
    {
        $category = Category::withTrashed()->findOrFail($categoryId);
        $category->restore();
        $this->clearOptionsCache($category->family_id);

        return $category->fresh();
    }

    /**
     * Retorna as categorias agrupadas por tipo com cache por familia.
     *
     * @return array
     */
    public function getGroupedOptions(): array
    {
        $familyId = app(TenantContext::class)->getFamilyId();
        $cacheKey = 'categories.grouped.' . ($familyId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Category::query()
                ->orderBy('name')
                ->get()
                ->groupBy('type')
                ->map->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Retorna as categorias de um tipo especifico.
     *
     * @param CategoryTypeEnum|null $type
     * @return array
     */
    public function getOptionsForType(?CategoryTypeEnum $type): array
    {
        if (!$type) {
            return [];
        }

        $grouped = $this->getGroupedOptions();

        return $grouped[$type->value] ?? [];
    }

    private function clearOptionsCache(?int $familyId): void
    {
        $cacheKey = 'categories.grouped.' . ($familyId ?? 'global');
        Cache::forget($cacheKey);
    }

    /**
     * Verifica se uma categoria existe.
     *
     * @param string $name
     * @param int|null $familyId
     * @param string $type
     * @param int|null $excludeId
     * @return bool
     */
    public function exists(
        string $name,
        ?int $familyId,
        string $type,
        ?int $excludeId = null
    ): bool {
        $query = Category::where('name', $name)
            ->where('family_id', $familyId)
            ->where('type', $type);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
