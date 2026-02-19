<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startAt = CarbonImmutable::create(2026, 4, 17, 19, 0, 0);

        $event = Event::query()->updateOrCreate(
            [
                'name' => 'Squash 24 sata 2026',
                'start_at' => $startAt,
            ],
            [
                'end_at' => $startAt->addHours(24),
            ]
        );

        $event->users()->sync(User::query()->pluck('id'));
    }
}
