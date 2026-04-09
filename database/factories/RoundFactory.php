<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Round>
 */
class RoundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = $this->faker->numberBetween(1, 2);

        return [
            'event_id' => Event::factory(),
            'number' => $number,
            'name' => "Round {$number}",
            'is_active' => true,
        ];
    }
}
