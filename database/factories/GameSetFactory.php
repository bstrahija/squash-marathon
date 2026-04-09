<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSet>
 */
class GameSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isExtended = $this->faker->boolean(30);
        $loserScore = $isExtended
            ? $this->faker->numberBetween(10, 15)
            : $this->faker->numberBetween(0, 9);
        $winnerScore = $isExtended ? $loserScore + 2 : 11;
        $playerOneWins = $this->faker->boolean();

        return [
            'game_id' => Game::factory(),
            'round_id' => fn (array $attributes): int => Game::query()->findOrFail($attributes['game_id'])->round_id,
            'group_id' => fn (array $attributes): int => Game::query()->findOrFail($attributes['game_id'])->group_id,
            'player_one_id' => User::factory(),
            'player_two_id' => User::factory(),
            'winner_id' => null,
            'player_one_score' => $playerOneWins ? $winnerScore : $loserScore,
            'player_two_score' => $playerOneWins ? $loserScore : $winnerScore,
        ];
    }
}
