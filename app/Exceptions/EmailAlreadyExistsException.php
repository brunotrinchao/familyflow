<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class EmailAlreadyExistsException extends Exception
{
    /**
     * O código de status HTTP recomendado para este tipo de erro (409 Conflict).
     */
    protected $code = 409;

    /**
     * A mensagem padrão da exceção.
     */
    protected $message = 'O e-mail fornecido já está em uso por outro usuário.';

    /**
     * Construtor da Exceção.
     * Permite passar uma mensagem personalizada.
     *
     * @param string $message Mensagem opcional para sobrescrever a padrão.
     * @param int $code Código opcional.
     * @param Throwable|null $previous Exceção anterior na cadeia.
     */
    public function __construct($message = null, $code = 0, Throwable $previous = null)
    {
        // Usa a mensagem padrão se nenhuma for fornecida.
        $message = $message ?? $this->message;

        // Chama o construtor da classe pai (Exception).
        parent::__construct($message, $code ?? $this->code, $previous);
    }

    /**
     * Opcional: Renderiza a exceção como uma resposta HTTP (útil para APIs no Laravel).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        // Se a requisição espera JSON (típico em APIs)
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error_code' => 'EMAIL_ALREADY_EXISTS'
            ], $this->code);
        }

        // Para outras requisições, retorna a resposta padrão de erro
        return parent::render($request);
    }
}
