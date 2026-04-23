<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ScheduleGameController extends Controller
{
    public function __invoke(GameSchedule $gameSchedule): RedirectResponse
    {
        $game = DB::transaction(function () use ($gameSchedule): Game {
            $schedule = GameSchedule::query()
                ->with('round')
                ->lockForUpdate()
                ->findOrFail($gameSchedule->getKey());

            if ($schedule->game_id) {
                return Game::query()->findOrFail((int) $schedule->game_id);
            }

            $round = $schedule->round;

            abort_unless($round !== null, 404);

            $game = Game::query()->create([
                'event_id'      => (int) $round->event_id,
                'round_id'      => (int) $schedule->round_id,
                'group_id'      => (int) $schedule->group_id,
                'best_of'       => 2,
                'player_one_id' => (int) $schedule->player_one_id,
                'player_two_id' => (int) $schedule->player_two_id,
            ]);

            $schedule->forceFill([
                'game_id' => (int) $game->id,
            ])->save();

            return $game;
        });

        return redirect()->route('matches.score', ['game' => $game->id]);
    }
}
