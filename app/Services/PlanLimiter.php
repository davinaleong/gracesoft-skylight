<?php

namespace App\Services;

use App\Models\Workspace;

class PlanLimiter
{
    public static function boardLimit(Workspace $workspace): ?int
    {
        return $workspace->planLimits()['board_limit'];
    }

    public static function memberLimit(Workspace $workspace): ?int
    {
        return $workspace->planLimits()['member_limit'];
    }

    public static function canCreateBoard(Workspace $workspace): bool
    {
        $limit = self::boardLimit($workspace);

        return $limit === null || $workspace->boards()->count() < $limit;
    }

    public static function canInviteMember(Workspace $workspace): bool
    {
        $limit = self::memberLimit($workspace);

        return $limit === null || $workspace->users()->count() < $limit;
    }
}
