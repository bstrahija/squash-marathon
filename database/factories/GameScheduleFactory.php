<?php

namespace Database\Factories;

use App\Models\GameSchedule;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSchedule>
 */
class GameScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id'       => null,
            'player_one_id' => User::factory(),
            'player_two_id' => User::factory(),
            'group_id'      => Group::factory(),
            'round_id'      => Round::factory(),
            'starts_at'     => null,
        ];
    }
}
