<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account Data Exports
    |--------------------------------------------------------------------------
    |
    | Exports covering more cards than the threshold are built in a queued
    | job and emailed as a signed link instead of downloading immediately.
    | Generated archives live on the "local" disk under "exports/" and are
    | pruned daily once older than the link lifetime.
    |
    */

    'queue_threshold_cards' => (int) env('EXPORT_QUEUE_THRESHOLD_CARDS', 500),

    'link_lifetime_hours' => (int) env('EXPORT_LINK_LIFETIME_HOURS', 24),

];
