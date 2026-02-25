<?php

namespace App\Filament\Resources\Games\Tables;

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
                TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable(),
                TextColumn::make('playerOne.full_name')
                    ->label('Player one')
                    ->searchable(),
                TextColumn::make('playerTwo.full_name')
                    ->label('Player two')
                    ->searchable(),
                TextColumn::make('best_of')
                    ->label('Best of')
                    ->sortable(),
                TextColumn::make('sets_count')
                    ->label('Sets')
                    ->counts('sets')
                    ->sortable(),
                TextColumn::make('sets_scores')
                    ->label('Scores')
                    ->getStateUsing(function ($record): string {
                        $scores = $record->sets
                            ->map(fn ($set): string => "{$set->player_one_score}-{$set->player_two_score}")
                            ->implode(', ');

                        return $scores !== '' ? $scores : '—';
                    })
                    ->wrap(),
                TextColumn::make('created_at')
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
}
