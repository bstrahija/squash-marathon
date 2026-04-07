<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Game;
use App\Models\Round;
use App\Models\Set;
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

        $rounds = Round::query()
            ->where('event_id', $event->id)
            ->with(['groups.users'])
            ->orderBy('number')
            ->get();

        if ($rounds->isEmpty()) {
            return;
        }

        $totalSeconds = max($endTimestamp - $startTimestamp, 1);
        $segmentSeconds = (int) floor($totalSeconds / max($rounds->count(), 1));

        foreach ($rounds as $roundIndex => $round) {
            $segmentStart = $startTimestamp + ($segmentSeconds * $roundIndex);
            $segmentEnd = $roundIndex === $rounds->count() - 1
                ? $endTimestamp
                : $segmentStart + $segmentSeconds;

            $matches = [];

            foreach ($round->groups as $group) {
                $players = $group->users->values();

                if ($players->count() < 2) {
                    continue;
                }

                for ($i = 0; $i < $players->count(); $i++) {
                    for ($j = $i + 1; $j < $players->count(); $j++) {
                        $matches[] = [$group, $players[$i], $players[$j]];
                        $matches[] = [$group, $players[$i], $players[$j]];
                    }
                }
            }

            if ($matches === []) {
                continue;
            }

            shuffle($matches);

            foreach ($matches as $index => [$group, $playerOne, $playerTwo]) {
                $segmentEndSafe = max($segmentEnd, $segmentStart);
                $createdAt = Carbon::createFromTimestamp(
                    random_int($segmentStart, $segmentEndSafe)
                );
                $gameDurationSeconds = random_int(900, 2700);
                $gameStartedAt = $createdAt->copy();
                $gameFinishedAt = $gameStartedAt->copy()->addSeconds($gameDurationSeconds);

                $game = Game::factory()->create([
                    'event_id' => $event->id,
                    'round_id' => $round->id,
                    'group_id' => $group->id,
                    'best_of' => 2,
                    'court_number' => ($index % 2) + 1,
                    'started_at' => $gameStartedAt,
                    'finished_at' => $gameFinishedAt,
                    'player_one_id' => $playerOne->id,
                    'player_two_id' => $playerTwo->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $outcome = random_int(0, 2);
                $setWinners = match ($outcome) {
                    0 => [true, true],
                    1 => [false, false],
                    default => [true, false],
                };

                $setStartAt = $gameStartedAt->copy();
                $remainingSeconds = $gameDurationSeconds;
                $setCount = count($setWinners);

                foreach ($setWinners as $setIndex => $playerOneWins) {
                    $scores = $this->generateSetScores($playerOneWins);

                    $minimumSeconds = 240;
                    $maxForSet = max($minimumSeconds, $remainingSeconds - ($minimumSeconds * ($setCount - $setIndex - 1)));
                    $setDurationSeconds = $setIndex === $setCount - 1
                        ? max($minimumSeconds, $remainingSeconds)
                        : random_int($minimumSeconds, $maxForSet);

                    $setFinishedAt = $setStartAt->copy()->addSeconds($setDurationSeconds);

                    Set::factory()->create([
                        'game_id' => $game->id,
                        'round_id' => $round->id,
                        'group_id' => $group->id,
                        'started_at' => $setStartAt,
                        'finished_at' => $setFinishedAt,
                        'player_one_id' => $playerOne->id,
                        'player_two_id' => $playerTwo->id,
                        'player_one_score' => $scores['player_one_score'],
                        'player_two_score' => $scores['player_two_score'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $setStartAt = $setFinishedAt->copy();
                    $remainingSeconds = max(0, $remainingSeconds - $setDurationSeconds);
                }
            }
        }
    }

    /**
     * @return array{player_one_score: int, player_two_score: int}
     */
    private function generateSetScores(bool $playerOneWins): array
    {
        $isExtended = (bool) random_int(0, 4);
        $loserScore = $isExtended ? random_int(10, 15) : random_int(0, 9);
        $winnerScore = $isExtended ? $loserScore + 2 : 11;

        return [
            'player_one_score' => $playerOneWins ? $winnerScore : $loserScore,
            'player_two_score' => $playerOneWins ? $loserScore : $winnerScore,
        ];
    }
}
