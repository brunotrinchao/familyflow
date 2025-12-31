<?php

namespace App\Services;

use App\Exceptions\EmailAlreadyExistsException;
use App\Models\Family;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UserService
{
    /**
     * Cria um novo usuário e o associa à família (Tenant) fornecida.
     *
     * @param array $data Os dados do usuário (incluindo 'email').
     * @param Family $activeFamily O Tenant ao qual o usuário será associado.
     * @return User
     * @throws EmailAlreadyExistsException
     * @throws Throwable
     */
    public static function create(array $data, Family $activeFamily): User
    {
        // 1. Lança exceção se o email já existe
        if (User::where('email', $data['email'])->exists()) {
            throw new EmailAlreadyExistsException('E-mail já existente.');
        }

        // Lógica de Criação
        $randomPassword = Str::random(10);
        $data['password'] = Hash::make($randomPassword);
        $user = User::create($data);

        // Associa o usuário ao Tenant/Family fornecido
        $user->families()->attach($activeFamily->id);

        // Retorna o usuário em caso de sucesso
        return $user;
    }

    public static function update(array $data, User $record): bool
    {


        try {

            return $record->fill($data)->save();

        } catch (Throwable $e) {
            // Log de erro
            Log::error('Erro ao atualizar conta.', [
                'message'        => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
                'data'           => $data
            ]);

            return false;
        }
    }
}
