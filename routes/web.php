<?php

use App\Models\Board;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('home') : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/home', fn () => view('home'))->name('home');
    Route::get('/profile', fn () => view('profile.index'))->name('profile');

    Route::get('/boards/{board}', function (Board $board) {
        abort_unless($board->user_id === auth()->id(), 403);

        return view('boards.show', ['board' => $board]);
    })->name('boards.show');
});
