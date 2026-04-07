<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
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
            'round_id' => fn (array $attributes): int => Round::factory()->create([
                'event_id' => $attributes['event_id'],
            ])->id,
            'number' => $number,
            'name' => "Group {$number}",
        ];
    }
}
