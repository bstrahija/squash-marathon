<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnableDebugbarForAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('debugbar')) {
            $isAdmin = (bool) $request->user()?->hasRole(RoleName::Admin->value);

            if ($isAdmin) {
                app('debugbar')->enable();
            } else {
                app('debugbar')->disable();
            }
        }

        return $next($request);
    }
}
