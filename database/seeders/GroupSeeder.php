<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Round;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rounds = Round::query()
            ->with('users')
            ->orderBy('number')
            ->get();

        if ($rounds->isEmpty()) {
            return;
        }

        foreach ($rounds as $round) {
            $users = $round->users->values();

            if ($users->isEmpty()) {
                continue;
            }

            $shuffled = $users->shuffle()->values();
            $firstHalf = $shuffled->slice(0, (int) ceil($shuffled->count() / 2));
            $secondHalf = $shuffled->slice($firstHalf->count());

            $groups = collect([
                1 => $firstHalf,
                2 => $secondHalf,
            ])->filter(fn ($members) => $members->isNotEmpty());

            foreach ($groups as $number => $members) {
                $group = Group::query()->firstOrCreate([
                    'event_id' => $round->event_id,
                    'round_id' => $round->id,
                    'number' => $number,
                ], [
                    'name' => "Grupa {$number}",
                ]);

                $group->users()->sync($members->pluck('id')->all());
            }
        }
    }
}
