<?php

namespace App\Filament\Resources\Games\Tables;

use App\Models\Game;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('sets'))
            ->columns([
                TextColumn::make('round.name')
                    ->label('Round')
                    ->sortable(),
                TextColumn::make('group.name')
                    ->label('Group')
                    ->sortable(),
                TextColumn::make('playerOne.full_name')
                    ->label('Player one')
                    ->searchable()
                    ->color(fn ($record): string => self::playerNameColor($record, $record->player_one_id)),
                TextColumn::make('playerTwo.full_name')
                    ->label('Player two')
                    ->searchable()
                    ->color(fn ($record): string => self::playerNameColor($record, $record->player_two_id)),
                TextColumn::make('set_score')
                    ->label('Set score')
                    ->getStateUsing(function ($record): string {
                        $result = self::matchResult($record);

                        if (! $result['is_complete']) {
                            return '—';
                        }

                        return "{$result['player_one_wins']}-{$result['player_two_wins']}";
                    })
                    ->sortable(),
                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => self::formatDuration($state))
                    ->sortable(),
                TextColumn::make('sets_scores')
                    ->label('Set points')
                    ->getStateUsing(function ($record): string {
                        $scores = $record->sets
                            ->map(fn ($set): string => "{$set->player_one_score}-{$set->player_two_score}")
                            ->implode(', ');

                        return $scores !== '' ? $scores : '—';
                    })
                    ->wrap(),
                TextColumn::make('created_at')
                    ->timezone(config('app.display_timezone'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function matchResult($record): array
    {
        return Game::determineMatchResultFromSetScores(
            $record->sets
                ->map(
                    fn ($set): array => [
                        'player_one_score' => $set->player_one_score,
                        'player_two_score' => $set->player_two_score,
                    ]
                )
                ->all(),
            $record->best_of,
            $record->player_one_id,
            $record->player_two_id,
        );
    }

    private static function playerNameColor($record, int $playerId): string
    {
        $result = self::matchResult($record);

        if (! $result['is_complete']) {
            return 'gray';
        }

        if ($result['is_draw']) {
            return 'warning';
        }

        if ($result['winner_id'] === $playerId) {
            return 'success';
        }

        return 'danger';
    }

    private static function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '—';
        }

        $minutes          = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}
