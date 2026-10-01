<?php

use App\Http\Controllers\AccountExportController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\BoardExportController;
use App\Http\Controllers\WorkspaceInviteController;
use App\Models\Board;
use App\Models\BoardShareLink;
use App\Models\ShareLinkAccess;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountDeletion;
use App\Services\ActivityLogger;
use App\Services\SystemStatusService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('home') : view('landing');
})->name('landing');

Route::get('/pricing', fn () => view('pricing'))->name('pricing');

Route::get('/changelog', fn () => view('changelog'))->name('changelog');

Route::get('/feedback', fn () => view('feedback'))->name('feedback');

// Public status page -- rate-limited since each load does real read/write
// checks against the cache and storage disks, not just a static page render.
Route::middleware(['throttle:status'])->get('/status', function (SystemStatusService $status) {
    $checks = $status->checks();

    return view('status', [
        'checks' => $checks,
        'healthy' => collect($checks)->every(fn ($check) => $check['healthy']),
    ]);
})->name('status');

Route::get('/security', fn () => view('security'))->name('security');

Route::get('/privacy', fn () => view('privacy'))->name('privacy');

Route::get('/terms', fn () => view('terms'))->name('terms');

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

    Route::post('/account/export', [AccountExportController::class, 'store'])
        ->middleware('throttle:3,10')
        ->name('account.export');

    Route::get('/account/exports/{file}', [AccountExportController::class, 'download'])
        ->middleware('signed')
        ->name('account.export.download');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', fn () => view('home'))->name('home');

    Route::get('/boards/{board}', function (Board $board) {
        abort_unless($board->workspace->hasMember(auth()->user()), 403);

        return view('boards.show', ['board' => $board]);
    })->name('boards.show');

    Route::get('/boards/{board}/export', BoardExportController::class)->name('boards.export');

    Route::get('/team', fn () => view('workspaces.team', ['workspace' => auth()->user()->currentWorkspace()]))
        ->name('team');

    Route::get('/team/{workspace}', function (Workspace $workspace) {
        abort_unless($workspace->hasMember(auth()->user()), 403);

        return view('workspaces.team', ['workspace' => $workspace]);
    })->name('team.show');

    Route::get('/billing', fn () => view('workspaces.billing'))->name('billing');

    Route::get('/webhooks', fn () => view('workspaces.webhooks'))->name('webhooks');

    Route::get('/integrations', fn () => view('workspaces.integrations'))->name('integrations');
});

Route::get('/invites/{token}', [WorkspaceInviteController::class, 'show'])->name('invites.show');
Route::post('/invites/{token}/accept', [WorkspaceInviteController::class, 'accept'])
    ->middleware(['auth'])
    ->name('invites.accept');

// Signed "Keep my account" link from the deletion email; works signed out.
Route::get('/account/deletion/cancel/{user}', function (User $user) {
    if ($user->deletion_scheduled_at !== null) {
        AccountDeletion::cancel($user);
    }

    return redirect()->route(auth()->check() ? 'profile' : 'login')->with('status', 'account-deletion-cancelled');
})->middleware(['signed', 'throttle:6,1'])->name('account.deletion.cancel');

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
