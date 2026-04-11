<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlayerController;
use App\Http\Middleware\EnsureUserCanManageMatches;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/matches', 'matches')->name('matches.index');
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

Route::redirect('/login', '/admin/login')->name('login');
Route::redirect('/register', '/admin/login')->name('register');
Route::redirect('/email/verify', '/admin')->name('verification.notice');
