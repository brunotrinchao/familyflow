<?php

namespace App\Http\Middleware;

use App\Models\FamilyUser;
use App\Services\TenantContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetFamilyContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            $familyUser = FamilyUser::where('user_id', auth()->id())
                ->where('family_id', $tenant->id)
                ->first();

            if ($familyUser) {
                app(TenantContext::class)->setFromFamilyUser($familyUser);
            }
        }

        return $next($request);
    }
}
