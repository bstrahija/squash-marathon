<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageMatches
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            (bool) $request->user()?->hasAnyRole([
                RoleName::Admin->value,
                RoleName::Player->value,
            ]),
            403,
        );

        return $next($request);
    }
}
