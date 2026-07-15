<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\WorkspaceInviteController;
use App\Models\Board;
use App\Models\BoardShareLink;
use App\Models\ShareLinkAccess;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('home') : redirect()->route('login');
});

Route::middleware(['web', 'guest'])->prefix('auth')->group(function () {
    Route::get('/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->whereIn('provider', SocialiteController::PROVIDERS)
        ->name('oauth.redirect');

    Route::get('/{provider}/callback', [SocialiteController::class, 'callback'])
        ->whereIn('provider', SocialiteController::PROVIDERS)
        ->name('oauth.callback');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', fn () => view('profile.index'))->name('profile');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', fn () => view('home'))->name('home');

    Route::get('/boards/{board}', function (Board $board) {
        abort_unless($board->workspace->hasMember(auth()->user()), 403);

        return view('boards.show', ['board' => $board]);
    })->name('boards.show');

    Route::get('/team', fn () => view('workspaces.team', ['workspace' => auth()->user()->currentWorkspace()]))
        ->name('team');

    Route::get('/team/{workspace}', function (Workspace $workspace) {
        abort_unless($workspace->hasMember(auth()->user()), 403);

        return view('workspaces.team', ['workspace' => $workspace]);
    })->name('team.show');
});

Route::get('/invites/{token}', [WorkspaceInviteController::class, 'show'])->name('invites.show');
Route::post('/invites/{token}/accept', [WorkspaceInviteController::class, 'accept'])
    ->middleware(['auth'])
    ->name('invites.accept');

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

        $relations = [
            'workspace',
            'columns.cards.labels',
            'columns.cards.checklists.items',
        ];

        if ($link->can_see_comments) {
            $relations[] = 'columns.cards.comments.user';
        }

        if ($link->can_see_attachments) {
            $relations[] = 'columns.cards.attachments';
        }

        return response()
            ->view('viewer.board', ['link' => $link, 'board' => $link->board->load($relations)])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    })->name('viewer');
});
