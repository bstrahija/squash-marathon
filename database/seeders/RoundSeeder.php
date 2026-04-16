<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = Event::current();

        if (! $event) {
            return;
        }

        $userIds = $event->users()->pluck('id')->all();

        if ($userIds === []) {
            $userIds = User::query()->pluck('id')->all();
        }

        for ($number = 1; $number <= 9; $number++) {
            $round = Round::query()->firstOrCreate([
                'event_id' => $event->id,
                'number'   => $number,
            ], [
                'name' => "Round {$number}",
            ]);

            if ($userIds !== []) {
                $round->users()->sync($userIds);
            }
        }
    }
}
