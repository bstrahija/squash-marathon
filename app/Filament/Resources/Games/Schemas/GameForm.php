<?php

namespace App\Filament\Resources\Games\Schemas;

use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->default(fn (): ?int => Event::query()->latest('start_at')->value('id'))
                    ->required()
                    ->searchable(),
                Select::make('best_of')
                    ->label('Best of')
                    ->options([
                        1 => 'Best of 1',
                        3 => 'Best of 3',
                        5 => 'Best of 5',
                    ])
                    ->default(1)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                        $count = max((int) $state, 1);
                        $sets = $get('sets') ?? [];

                        if (! is_array($sets)) {
                            $sets = [];
                        }

                        $current = count($sets);

                        if ($current < $count) {
                            for ($i = $current; $i < $count; $i++) {
                                $sets[] = [];
                            }
                        } elseif ($current > $count) {
                            $sets = array_slice($sets, 0, $count, true);
                        }

                        $position = 1;
                        foreach ($sets as $key => $setState) {
                            $sets[$key] = array_merge(
                                is_array($setState) ? $setState : [],
                                ['set_number' => $position]
                            );
                            $position++;
                        }

                        $set('sets', $sets);
                    }),
                Select::make('player_one_id')
                    ->label('Player one')
                    ->relationship('playerOne', 'email')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->required()
                    ->preload()
                    ->searchable(),
                Select::make('player_two_id')
                    ->label('Player two')
                    ->relationship('playerTwo', 'email')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->required()
                    ->preload()
                    ->searchable(),
                Repeater::make('sets')
                    ->relationship()
                    ->columns([
                        'default' => 2,
                        'sm' => 2,
                        'md' => 2,
                        'lg' => 2,
                        'xl' => 2,
                        '2xl' => 2,
                    ])
                    ->itemLabel(function (array $state): string {
                        $setNumber = (int) data_get($state, 'set_number', 0);

                        return self::formatSetLabel($setNumber);
                    })
                    ->defaultItems(fn (Get $get): int => max((int) $get('best_of'), 1))
                    ->afterStateHydrated(function (Set $set, ?array $state): void {
                        if (! is_array($state)) {
                            return;
                        }

                        $position = 1;
                        foreach ($state as $key => $setState) {
                            $state[$key] = array_merge(
                                is_array($setState) ? $setState : [],
                                ['set_number' => $position]
                            );
                            $position++;
                        }

                        $set('sets', $state);
                    })
                    ->schema([
                        Hidden::make('set_number')
                            ->dehydrated(),
                        Hidden::make('player_one_id')
                            ->dehydrateStateUsing(fn (Get $get): ?int => $get('../../player_one_id'))
                            ->dehydrated(),
                        Hidden::make('player_two_id')
                            ->dehydrateStateUsing(fn (Get $get): ?int => $get('../../player_two_id'))
                            ->dehydrated(),
                        TextInput::make('player_one_score')
                            ->hiddenLabel()
                            ->numeric()
                            ->minValue(0)
                            ->required(fn (Get $get): bool => self::shouldRequireSetScores($get)),
                        TextInput::make('player_two_score')
                            ->hiddenLabel()
                            ->numeric()
                            ->minValue(0)
                            ->required(fn (Get $get): bool => self::shouldRequireSetScores($get)),
                    ])
                    ->minItems(fn (Get $get): int => max((int) $get('best_of'), 1))
                    ->maxItems(fn (Get $get): int => max((int) $get('best_of'), 1))
                    ->addable(fn (Get $get): bool => (int) $get('best_of') > 1)
                    ->deletable(false)
                    ->columnSpanFull(),
                Placeholder::make('match_winner_preview')
                    ->label('Game winner')
                    ->content(function (Get $get): string {
                        $playerOneId = $get('player_one_id');
                        $playerTwoId = $get('player_two_id');
                        $bestOf = (int) $get('best_of');
                        $sets = $get('sets') ?? [];

                        if (! $playerOneId || ! $playerTwoId) {
                            return 'Select players to calculate the game winner.';
                        }

                        if (! is_array($sets)) {
                            return 'Enter set scores to calculate the game winner.';
                        }

                        $winnerId = Game::determineWinnerIdFromSetScores(
                            $sets,
                            $bestOf,
                            (int) $playerOneId,
                            (int) $playerTwoId
                        );

                        if (! $winnerId) {
                            return 'No match winner yet.';
                        }

                        $names = User::query()
                            ->whereKey([$playerOneId, $playerTwoId])
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => $user->full_name]);

                        return $names->get($winnerId, 'Winner unavailable.');
                    })
                    ->columnSpanFull(),
            ]);
    }

    private static function shouldRequireSetScores(Get $get): bool
    {
        if (filled($get('player_one_score')) || filled($get('player_two_score'))) {
            return true;
        }

        $bestOf = max((int) $get('../../best_of'), 1);
        $setNumber = (int) $get('set_number');
        $targetWins = (int) ceil($bestOf / 2);

        if ($setNumber <= $targetWins) {
            return true;
        }

        $playerOneId = $get('../../player_one_id');
        $playerTwoId = $get('../../player_two_id');

        if (! $playerOneId || ! $playerTwoId) {
            return false;
        }

        $sets = $get('../../sets') ?? [];

        if (! is_array($sets)) {
            return false;
        }

        $previousSets = array_values(array_filter($sets, function ($set) use ($setNumber): bool {
            return (int) data_get($set, 'set_number') < $setNumber;
        }));

        $winnerId = Game::determineWinnerIdFromSetScores(
            $previousSets,
            $bestOf,
            (int) $playerOneId,
            (int) $playerTwoId
        );

        return $winnerId === null;
    }

    private static function formatSetLabel(int $setNumber): string
    {
        if ($setNumber < 1) {
            return 'Set';
        }

        $suffix = match ($setNumber % 100) {
            11, 12, 13 => 'th',
            default => match ($setNumber % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            },
        };

        return $setNumber.$suffix.' set';
    }
}
