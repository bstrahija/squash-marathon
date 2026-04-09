<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieAppearance = $request->cookie('appearance');

        if (in_array($cookieAppearance, ['light', 'dark', 'system'], true)) {
            $request->session()->put('appearance', $cookieAppearance);
        }

        $appearance = $request->session()->get('appearance', $cookieAppearance ?? 'system');

        if (! in_array($appearance, ['light', 'dark', 'system'], true)) {
            $appearance = 'system';
        }

        View::share('appearance', $appearance);

        return $next($request);
    }
}
