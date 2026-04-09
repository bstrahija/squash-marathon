<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('avatar')
                    ->collection('avatar')
                    ->disk('public')
                    ->visibility('public')
                    ->saveUploadedFileUsing(function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
                        if (! $record || ! method_exists($record, 'addMediaFromString')) {
                            return null;
                        }

                        $contents = $file->get();

                        if (! is_string($contents) || $contents === '') {
                            return null;
                        }

                        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
                        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'avatar';

                        $media = $record
                            ->addMediaFromString($contents)
                            ->usingFileName(sprintf('%s.%s', Str::uuid(), $extension))
                            ->usingName($name)
                            ->toMediaCollection($component->getCollection() ?? 'avatar', 'public');

                        return $media->getAttributeValue('uuid');
                    })
                    ->columnSpanFull(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null),
                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
                Select::make('events')
                    ->label('Registered events')
                    ->multiple()
                    ->relationship('events', 'name')
                    ->preload()
                    ->searchable(),
            ]);
    }
}
