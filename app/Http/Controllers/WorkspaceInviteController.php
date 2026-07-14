<?php

namespace App\Http\Controllers;

use App\Models\WorkspaceInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkspaceInviteController extends Controller
{
    public function show(string $token): View
    {
        $invite = WorkspaceInvite::findByToken($token);

        abort_if(! $invite, 404);

        $invite->load('workspace', 'inviter');

        return view('invites.show', ['invite' => $invite, 'token' => $token]);
    }

    public function accept(string $token): RedirectResponse
    {
        $invite = WorkspaceInvite::findByToken($token);

        abort_if(! $invite, 404);

        abort_unless($invite->isPending(), 410);

        abort_unless(
            str($invite->email)->lower()->is(str(Auth::user()->email)->lower()),
            403,
            'This invitation was sent to a different email address.'
        );

        $invite->accept(Auth::user());

        return redirect()->route('home')->with('status', 'You\'ve joined '.$invite->workspace->name.'.');
    }
}
