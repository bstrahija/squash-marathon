<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->collection('photo')
                    ->conversion('thumb')
                    ->label('Photo')
                    ->square(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('start_at')
                    ->label('Starts')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Ends')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Players')
                    ->counts('users')
                    ->sortable(),
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
