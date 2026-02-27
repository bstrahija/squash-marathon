<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Game;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventId = Event::query()->latest('start_at')->value('id');

        if (! $eventId) {
            return;
        }

        $players = User::query()->get();

        if ($players->count() < 2) {
            return;
        }

        for ($i = 0; $i < 150; $i++) {
            [$playerOne, $playerTwo] = $players->random(2)->values();

            $game = Game::factory()->create([
                'event_id' => $eventId,
                'best_of' => 1,
                'player_one_id' => $playerOne->id,
                'player_two_id' => $playerTwo->id,
            ]);

            $scores = $this->generateSetScores();

            Set::factory()->create([
                'game_id' => $game->id,
                'player_one_id' => $playerOne->id,
                'player_two_id' => $playerTwo->id,
                'player_one_score' => $scores['player_one_score'],
                'player_two_score' => $scores['player_two_score'],
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
