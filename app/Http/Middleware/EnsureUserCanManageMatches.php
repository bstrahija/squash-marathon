<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Models\Game;
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
        $user = $request->user();

        abort_unless((bool) $user, 403);

        if ($user->hasRole(RoleName::Admin->value)) {
            return $next($request);
        }

        $gameId = $request->route('game');

        if ($gameId === null) {
            return $next($request);
        }

        $gameId = is_object($gameId) ? (int) $gameId->getKey() : (int) $gameId;

        $game = Game::query()->find($gameId);

        abort_unless($game !== null, 404);

        abort_if($game->finished_at !== null, 403);

        return $next($request);
    }
}
