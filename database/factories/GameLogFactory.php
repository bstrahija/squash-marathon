<?php

namespace Database\Factories;

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameLog>
 */
class GameLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $side = fake()->randomElement(GameLogSide::cases());
        $type = fake()->randomElement(GameLogType::cases());

        return [
            'game_id' => Game::factory(),
            'player_one_id' => fn (array $attributes): ?int => Game::query()->find($attributes['game_id'])?->player_one_id,
            'player_two_id' => fn (array $attributes): ?int => Game::query()->find($attributes['game_id'])?->player_two_id,
            'sequence' => fake()->numberBetween(1, 20),
            'type' => $type,
            'side' => $side,
            'player_one_score' => fake()->numberBetween(0, 11),
            'player_two_score' => fake()->numberBetween(0, 11),
            'player_one_sets' => fake()->numberBetween(0, 2),
            'player_two_sets' => fake()->numberBetween(0, 2),
        ];
    }
}
