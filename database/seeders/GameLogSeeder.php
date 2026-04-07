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

            for ($sequence = 1; $sequence <= 5; $sequence++) {
                $type = $sequence % 5 === 0 ? GameLogType::Let : GameLogType::Score;
                $side = fake()->randomElement(GameLogSide::cases());

                if ($type === GameLogType::Score) {
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
                    'player_one_score' => $playerOneScore,
                    'player_two_score' => $playerTwoScore,
                    'player_one_sets' => 0,
                    'player_two_sets' => 0,
                ]);
            }
        }
    }
}
