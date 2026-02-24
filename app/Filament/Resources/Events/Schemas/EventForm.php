<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('start_at')
                    ->label('Starts at')
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('Ends at')
                    ->seconds(false)
                    ->after('start_at')
                    ->required(),
                SpatieMediaLibraryFileUpload::make('photo')
                    ->collection('photo')
                    ->image()
                    ->imageEditor()
                    ->columnSpanFull(),
                Select::make('users')
                    ->label('Registered users')
                    ->multiple()
                    ->relationship('users', 'email')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
            ]);
    }
}
