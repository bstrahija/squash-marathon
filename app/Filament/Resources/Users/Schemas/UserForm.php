<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->columnSpanFull()
                    ->schema([
                        Section::make('User details')
                            ->schema([
                                TextInput::make('first_name')
                                    ->required(),
                                TextInput::make('last_name')
                                    ->required(),
                                TextInput::make('email')
                                    ->label('Email address')
                                    ->email()
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('password')
                                    ->password()
                                    ->confirmed()
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null),
                                TextInput::make('password_confirmation')
                                    ->label('Confirm password')
                                    ->password()
                                    ->dehydrated(false)
                                    ->required(fn (string $context): bool => $context === 'create'),
                                Select::make('events')
                                    ->label('Registered events')
                                    ->multiple()
                                    ->relationship('events', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                        Section::make('Meta')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('avatar')
                                    ->collection('avatar')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->image(),
                                Select::make('roles')
                                    ->multiple()
                                    ->relationship('roles', 'name')
                                    ->preload()
                                    ->searchable(),
                            ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),
                    ]),
            ]);
    }
}
