<?php

namespace Database\Seeders;

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use App\Models\GameLog;
use Illuminate\Database\Seeder;

class GameLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = Game::query()->get();

        foreach ($games as $game) {
            if (! $game->player_one_id || ! $game->player_two_id) {
                continue;
            }

            $playerOneScore = 0;
            $playerTwoScore = 0;
            $servingPlayer = null;
            $servingSide = GameLogSide::Right;
            $servingPending = true;

            for ($sequence = 1; $sequence <= 5; $sequence++) {
                $type = $sequence % 5 === 0 ? GameLogType::Let : GameLogType::Score;
                $side = fake()->randomElement(GameLogSide::cases());

                if ($type === GameLogType::Score) {
                    if ($servingPlayer === null) {
                        $servingPlayer = $side;
                    }

                    if ($side === $servingPlayer) {
                        if ($servingPending) {
                            $servingPending = false;
                        } else {
                            $servingSide = $servingSide === GameLogSide::Right
                                ? GameLogSide::Left
                                : GameLogSide::Right;
                        }
                    } else {
                        $servingPlayer = $side;
                        $servingSide = GameLogSide::Right;
                        $servingPending = true;
                    }

                    if ($side === GameLogSide::Left) {
                        $playerOneScore++;
                    } else {
                        $playerTwoScore++;
                    }
                }

                GameLog::query()->create([
                    'game_id' => $game->id,
                    'player_one_id' => $game->player_one_id,
                    'player_two_id' => $game->player_two_id,
                    'sequence' => $sequence,
                    'type' => $type,
                    'side' => $side,
                    'serving_player_id' => $servingPlayer === GameLogSide::Left
                        ? $game->player_one_id
                        : ($servingPlayer === GameLogSide::Right ? $game->player_two_id : null),
                    'serving_side' => $servingSide,
                    'player_one_score' => $playerOneScore,
                    'player_two_score' => $playerTwoScore,
                    'player_one_sets' => 0,
                    'player_two_sets' => 0,
                ]);
            }
        }
    }
}
