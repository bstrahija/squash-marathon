<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('user can have an avatar photo', function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public']);

    $user = User::factory()->create();

    $media = $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 400, 400))
        ->toMediaCollection('avatar');

    expect($user->getFirstMedia('avatar'))->not()->toBeNull();
    expect($user->getFirstMediaUrl('avatar', 'thumb'))->not()->toBe('');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
});

test('event can have a photo', function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public']);

    $event = Event::factory()->create();

    $media = $event->addMedia(UploadedFile::fake()->image('event.jpg', 1200, 630))
        ->toMediaCollection('photo');

    expect($event->getFirstMedia('photo'))->not()->toBeNull();
    expect($event->getFirstMediaUrl('photo', 'thumb'))->not()->toBe('');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
});

test('fallback urls are returned when media is missing', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    expect($user->avatarUrl())->toContain('placeholder-avatar.svg');
    expect($event->photoUrl())->toContain('placeholder-event.svg');
});
