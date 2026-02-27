<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Game;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = Event::query()->latest('start_at')->first();

        if (! $event) {
            return;
        }

        $startAt = $event->start_at ?? $event->created_at;
        $endAt = $event->end_at ?? $event->created_at;

        if (! $startAt || ! $endAt) {
            return;
        }

        $startTimestamp = $startAt->getTimestamp();
        $endTimestamp = $endAt->getTimestamp();

        if ($endTimestamp < $startTimestamp) {
            return;
        }

        $players = User::query()->get();

        if ($players->count() < 2) {
            return;
        }

        for ($i = 0; $i < 150; $i++) {
            [$playerOne, $playerTwo] = $players->random(2)->values();

            $createdAt = Carbon::createFromTimestamp(
                random_int($startTimestamp, $endTimestamp)
            );

            $game = Game::factory()->create([
                'event_id' => $event->id,
                'best_of' => 1,
                'player_one_id' => $playerOne->id,
                'player_two_id' => $playerTwo->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $scores = $this->generateSetScores();

            Set::factory()->create([
                'game_id' => $game->id,
                'player_one_id' => $playerOne->id,
                'player_two_id' => $playerTwo->id,
                'player_one_score' => $scores['player_one_score'],
                'player_two_score' => $scores['player_two_score'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * @return array{player_one_score: int, player_two_score: int}
     */
    private function generateSetScores(): array
    {
        $isExtended = (bool) random_int(0, 4);
        $loserScore = $isExtended ? random_int(10, 15) : random_int(0, 9);
        $winnerScore = $isExtended ? $loserScore + 2 : 11;
        $playerOneWins = (bool) random_int(0, 1);

        return [
            'player_one_score' => $playerOneWins ? $winnerScore : $loserScore,
            'player_two_score' => $playerOneWins ? $loserScore : $winnerScore,
        ];
    }
}
