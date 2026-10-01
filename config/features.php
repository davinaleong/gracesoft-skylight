<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roadmap Feature Flags
    |--------------------------------------------------------------------------
    |
    | Each roadmap milestone ships behind a Pennant feature. A feature is on
    | for everyone when "enabled" is true, otherwise only for the listed
    | early-access emails (comma-separated in the env variable).
    |
    | Pennant stores each user's resolved value, so after changing these
    | settings run `php artisan pennant:purge` to re-resolve.
    |
    */

    'm0_foundations' => [
        'enabled' => (bool) env('FEATURE_M0_ENABLED', false),
        'early_access' => array_values(array_filter(array_map(
            fn (string $email): string => strtolower(trim($email)),
            explode(',', (string) env('FEATURE_EARLY_ACCESS_EMAILS', '')),
        ))),
    ],

];
