<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user?->isAdmin()
            && ! $user->hasConfirmedTwoFactor()
            && ! $request->routeIs('two-factor.setup', 'two-factor.confirm', 'logout')
        ) {
            return redirect()->route('two-factor.setup');
        }

        return $next($request);
    }
}
