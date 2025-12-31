<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFamilySubscription
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtém o objeto Tenant (Família) ativo
//        $family = Filament::getTenant();
//
//        // Se o usuário não está em um Tenant (ex: página de login ou Super Admin), permite
//        if (!$family) {
//            return $next($request);
//        }
//
//        // Verifica a lógica de acesso definida no modelo Family
//        if (!$family->canAccess()) {
//
//            // Opcional: Destrói a sessão do usuário no Tenant
//            Filament::auth()->logout();
//
//            // Redireciona para uma página pública (fora do escopo do Tenant)
//            return redirect()->route('filament.admin.auth.login')
//                ->with('error', 'Seu acesso expirou. Regularize o pagamento para continuar.');
//        }
//
//        return $next($request);
    }
}
