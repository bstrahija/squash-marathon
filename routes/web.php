<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');
Route::view('/matches', 'matches')->name('matches.index');
Route::view('/matches/create', 'matches-create')->name('matches.create');
Route::view('/matches/{game}/score', 'matches-score')->whereNumber('game')->name('matches.score');
Route::get('/players/{user}', [PlayerController::class, 'show'])->whereNumber('user')->name('players.show');
Route::view('/tv', 'tv')->name('tv');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
