<?php

namespace App\Features;

use App\Models\User;

/**
 * Gates the user-facing M0 roadmap features (notification preferences,
 * account export, account deletion, PWA install). Roll out to the
 * early-access emails first, then flip FEATURE_M0_ENABLED for everyone.
 */
class M0Foundations
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(?User $user): bool
    {
        if (config('features.m0_foundations.enabled')) {
            return true;
        }

        return $user !== null
            && in_array(strtolower($user->email), config('features.m0_foundations.early_access'), true);
    }
}
