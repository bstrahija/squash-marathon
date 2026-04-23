<?php

namespace App\Filament\Resources\Games\Schemas;

use App\Models\Game;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
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
                    ->required()
                    ->preload()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('round_id', null);
                        $set('group_id', null);
                    }),
                Select::make('round_id')
                    ->label('Round')
                    ->options(function (Get $get): array {
                        $eventId = $get('event_id');

                        if (! $eventId) {
                            return [];
                        }

                        return Round::query()
                            ->where('event_id', $eventId)
                            ->orderBy('number')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                        if (! $state) {
                            $set('group_id', null);

                            return;
                        }

                        $eventId = Round::query()->whereKey($state)->value('event_id');

                        if ($eventId && (int) $eventId !== (int) $get('event_id')) {
                            $set('event_id', $eventId);
                        }

                        $set('group_id', null);
                    })
                    ->disabled(fn (Get $get): bool => blank($get('event_id'))),
                Select::make('group_id')
                    ->label('Group')
                    ->options(function (Get $get): array {
                        $roundId = $get('round_id');

                        if (! $roundId) {
                            return [];
                        }

                        return Group::query()
                            ->where('round_id', $roundId)
                            ->orderBy('number')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->required()
                    ->searchable()
                    ->disabled(fn (Get $get): bool => blank($get('round_id'))),
                Select::make('best_of')
                    ->label('Best of')
                    ->options([
                        2 => 'Best of 2',
                    ])
                    ->default(2)
                    ->required()
                    ->live()
                    ->disabled()
                    ->dehydrated()
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                        $count = max((int) $state, 1);
                        $sets  = $get('sets') ?? [];

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
                DateTimePicker::make('started_at')
                    ->label('Started at')
                    ->timezone(config('app.display_timezone'))
                    ->seconds(false),
                DateTimePicker::make('finished_at')
                    ->label('Finished at')
                    ->timezone(config('app.display_timezone'))
                    ->seconds(false),
                Repeater::make('sets')
                    ->relationship()
                    ->columns([
                        'default' => 2,
                        'sm'      => 2,
                        'md'      => 2,
                        'lg'      => 2,
                        'xl'      => 2,
                        '2xl'     => 2,
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
                            ->minValue(0),
                        TextInput::make('player_two_score')
                            ->hiddenLabel()
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->minItems(fn (Get $get): int => max((int) $get('best_of'), 1))
                    ->maxItems(fn (Get $get): int => max((int) $get('best_of'), 1))
                    ->addable(fn (Get $get): bool => (int) $get('best_of') > 1)
                    ->deletable(false)
                    ->columnSpanFull(),
                Placeholder::make('match_winner_preview')
                    ->label('Match outcome')
                    ->content(function (Get $get): string {
                        $playerOneId = $get('player_one_id');
                        $playerTwoId = $get('player_two_id');
                        $bestOf      = (int) $get('best_of');
                        $sets        = $get('sets') ?? [];

                        if (! $playerOneId || ! $playerTwoId) {
                            return 'Select players to calculate the game winner.';
                        }

                        if (! is_array($sets)) {
                            return 'Enter set scores to calculate the game winner.';
                        }

                        $result = Game::determineMatchResultFromSetScores(
                            $sets,
                            $bestOf,
                            (int) $playerOneId,
                            (int) $playerTwoId
                        );

                        if (! $result['is_complete']) {
                            return 'Match not complete yet.';
                        }

                        if ($result['is_draw']) {
                            return 'Match ends in a draw.';
                        }

                        $names = User::query()
                            ->whereKey([$playerOneId, $playerTwoId])
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => $user->full_name]);

                        return $names->get($result['winner_id'], 'Winner unavailable.');
                    })
                    ->columnSpanFull(),
            ]);
    }

    private static function formatSetLabel(int $setNumber): string
    {
        if ($setNumber < 1) {
            return 'Set';
        }

        $suffix = match ($setNumber % 100) {
            11, 12, 13 => 'th',
            default => match ($setNumber % 10) {
                1       => 'st',
                2       => 'nd',
                3       => 'rd',
                default => 'th',
            },
        };

        return $setNumber . $suffix . ' set';
    }
}
