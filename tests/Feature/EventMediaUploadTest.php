<?php

use App\Enums\RoleName;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function actingAsAdminUser(): User
{
    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    return $user;
}

test('filament event create saves media to spatie media library', function () {
    $this->actingAs(actingAsAdminUser());

    $fakeImage = UploadedFile::fake()->image('test-photo.jpg');

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'name'     => 'Test Event Upload',
            'start_at' => now()->addDays(1),
            'end_at'   => now()->addDays(2),
            'photo'    => $fakeImage,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::where('name', 'Test Event Upload')->first();

    expect($event)->not->toBeNull();
    expect($event->getMedia('photo'))->toHaveCount(1);
});

test('filament event edit saves media to spatie media library', function () {
    $this->actingAs(actingAsAdminUser());

    $event = Event::factory()->create();

    $fakeImage = UploadedFile::fake()->image('test-photo.jpg');

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm([
            'photo' => $fakeImage,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($event->fresh()->getMedia('photo'))->toHaveCount(1);
});

test('filament user create saves avatar to spatie media library', function () {
    $this->actingAs(actingAsAdminUser());

    $fakeImage = UploadedFile::fake()->image('test-avatar.jpg');
    $email     = 'filament-user-avatar@example.com';

    Livewire::test(CreateUser::class)
        ->fillForm([
            'first_name'            => 'Filament',
            'last_name'             => 'Avatar',
            'email'                 => $email,
            'password'              => 'password',
            'password_confirmation' => 'password',
            'avatar'                => $fakeImage,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', $email)->first();

    expect($user)->not->toBeNull();
    expect($user->getMedia('avatar'))->toHaveCount(1);
});
