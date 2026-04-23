<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SocialiteAuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Middleware\EnsureUserCanManageMatches;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/oauth/google/redirect', [SocialiteAuthenticatedSessionController::class, 'redirectToGoogle'])->name('oauth.google.redirect');
    Route::get('/oauth/google/callback', [SocialiteAuthenticatedSessionController::class, 'handleGoogleCallback'])->name('oauth.google.callback');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
});

Route::get('/', HomeController::class)->name('home');
Route::get('/schedule', ScheduleController::class)->name('schedule.index');
Route::view('/matches', 'matches')->name('matches.index');
Route::view('/stats', 'stats')->name('stats.index');
Route::view('/rounds', 'rounds')->name('rounds.index');
Route::view('/rounds/create', 'rounds-create')
    ->middleware(['auth', 'verified', EnsureUserIsAdmin::class])
    ->name('rounds.create');
Route::view('/rounds/{round}/edit', 'rounds-edit')
    ->whereNumber('round')
    ->middleware(['auth', 'verified', EnsureUserIsAdmin::class])
    ->name('rounds.edit');
Route::view('/matches/create', 'matches-create')
    ->middleware(['auth', 'verified', EnsureUserCanManageMatches::class])
    ->name('matches.create');
Route::view('/matches/{game}/score', 'matches-score')
    ->whereNumber('game')
    ->middleware(['auth', 'verified', EnsureUserCanManageMatches::class])
    ->name('matches.score');
Route::get('/players/{user}', [PlayerController::class, 'show'])->whereNumber('user')->name('players.show');
Route::view('/tv', 'tv')->name('tv');
Route::get('/tv/{group}', function (int $group) {
    return view('tv-group', [
        'groupNumber' => $group,
    ]);
})->where('group', '[1-9][0-9]*')->name('tv.group');

Route::get('/overlay/{group}', function (int $group) {
    return view('overlay-group', [
        'groupNumber' => $group,
    ]);
})->where('group', '[1-9][0-9]*')->name('overlay.group');

Route::redirect('/register', '/admin/login')->name('register');
Route::redirect('/email/verify', '/admin')->name('verification.notice');
