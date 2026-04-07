<?php

namespace App\Filament\Resources\Rounds\Schemas;

use App\Models\Event;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class RoundForm
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
                Select::make('number')
                    ->label('Round number')
                    ->options([
                        1 => 'Round 1',
                        2 => 'Round 2',
                        3 => 'Round 3',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                        if (! $state) {
                            return;
                        }

                        $name = $get('name');

                        if (blank($name) || str_starts_with((string) $name, 'Round ')) {
                            $set('name', "Round {$state}");
                        }
                    }),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('users')
                    ->label('Players')
                    ->multiple()
                    ->relationship('users', 'email')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
            ]);
    }
}
