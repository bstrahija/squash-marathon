<?php

namespace App\Filament\Resources\Groups\Schemas;

use App\Models\Round;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class GroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->label('Event')
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn (Get $get): bool => filled($get('event_id'))),
                Select::make('round_id')
                    ->label('Round')
                    ->relationship('round', 'name')
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                        if (! $state) {
                            return;
                        }

                        $eventId = Round::query()->whereKey($state)->value('event_id');
                        $set('event_id', $eventId);
                    }),
                Select::make('number')
                    ->label('Group number')
                    ->options([
                        1 => 'Group 1',
                        2 => 'Group 2',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                        if (! $state) {
                            return;
                        }

                        $name = $get('name');

                        if (blank($name) || str_starts_with((string) $name, 'Group ')) {
                            $set('name', "Group {$state}");
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
