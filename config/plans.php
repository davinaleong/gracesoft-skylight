<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pricing Tiers
    |--------------------------------------------------------------------------
    |
    | Each workspace subscribes to exactly one plan (Workspace::plan, default
    | "free"). Limits are enforced in App\Services\PlanLimiter. stripe_price_id
    | is null for the free tier (nothing to check out) and a placeholder for
    | the paid tiers until real Stripe Price ids are created in the dashboard.
    |
    */

    'free' => [
        'name' => 'Free',
        'price_monthly' => 0,
        'board_limit' => 3,
        'member_limit' => 3,
        'stripe_price_id' => null,
    ],

    'pro' => [
        'name' => 'Pro',
        'price_monthly' => 12,
        'board_limit' => 25,
        'member_limit' => 10,
        'stripe_price_id' => env('STRIPE_PRICE_PRO'),
    ],

    'team' => [
        'name' => 'Team',
        'price_monthly' => 29,
        'board_limit' => null, // unlimited
        'member_limit' => null, // unlimited
        'stripe_price_id' => env('STRIPE_PRICE_TEAM'),
    ],

];
