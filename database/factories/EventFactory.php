<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = $this->faker->dateTimeBetween('now', '+2 weeks');

        return [
            'name' => $this->faker->words(3, true),
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+24 hours'),
        ];
    }
}
