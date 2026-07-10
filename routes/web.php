<?php

use App\Models\Board;
use App\Models\BoardShareLink;
use App\Models\ShareLinkAccess;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Request;
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

// Public read-only board viewer — rate-limited, noindex
Route::middleware(['throttle:viewer'])->group(function () {
    Route::get('/view/{token}', function (string $token) {
        $link = BoardShareLink::findByToken($token);

        abort_if(! $link, 404);

        // Log access (hashed IP)
        ShareLinkAccess::create([
            'board_share_link_id' => $link->id,
            'ip_hash' => ActivityLogger::hashIp(Request::ip()),
            'user_agent' => substr(Request::userAgent() ?? '', 0, 500),
            'accessed_at' => now(),
        ]);

        ActivityLogger::log('share_link.accessed', $link->board, null, null);

        return response()
            ->view('viewer.board', ['link' => $link, 'board' => $link->board->load('columns.cards')])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    })->name('viewer');
});
