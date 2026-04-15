<?php

use App\Filament\Auth\LoginResponse as CustomLoginResponse;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

test('filament login redirects user with id one to admin panel', function () {
    $user = User::query()->find(1) ?? User::factory()->create(['id' => 1]);

    $request = Request::create('/admin/login', 'POST');
    $request->setLaravelSession(session()->driver());
    $request->setUserResolver(fn (): User => $user);

    $response = app(LoginResponseContract::class)->toResponse($request);

    expect(app(LoginResponseContract::class))->toBeInstanceOf(CustomLoginResponse::class);
    expect($response->getTargetUrl())->toBe(Filament::getUrl());
});

test('filament login redirects other users to homepage with success status', function () {
    User::factory()->create();
    $user = User::factory()->create();

    $request = Request::create('/admin/login', 'POST');
    $request->setLaravelSession(session()->driver());
    $request->setUserResolver(fn (): User => $user);

    $response = app(LoginResponseContract::class)->toResponse($request);

    expect($response->getTargetUrl())->toBe(route('home'));
    expect(session('status'))->toBe('Prijavljeni ste');
});
