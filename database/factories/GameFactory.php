<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'round_id' => fn (array $attributes): int => Round::factory()->create([
                'event_id' => $attributes['event_id'],
            ])->id,
            'group_id' => fn (array $attributes): int => Group::factory()->create([
                'event_id' => $attributes['event_id'],
                'round_id' => $attributes['round_id'],
            ])->id,
            'best_of' => 2,
            'court_number' => $this->faker->numberBetween(1, 2),
            'player_one_id' => User::factory(),
            'player_two_id' => User::factory(),
        ];
    }
}
