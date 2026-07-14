<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Workspace;

class UserObserver
{
    /**
     * Every user gets exactly one personal workspace, created the moment
     * their account row exists — covers registration, OAuth signup,
     * factories, seeders, and admin/tinker-created accounts alike.
     */
    public function created(User $user): void
    {
        Workspace::createForUser($user);
    }
}
