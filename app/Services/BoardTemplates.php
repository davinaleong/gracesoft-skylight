<?php

namespace App\Services;

use App\Models\Board;

class BoardTemplates
{
    /**
     * @var array<string, array{label: string, columns: array<int, string>}>
     */
    public const TEMPLATES = [
        'blank' => [
            'label' => 'Blank board',
            'columns' => [],
        ],
        'sprint' => [
            'label' => 'Sprint board',
            'columns' => ['Backlog', 'To Do', 'In Progress', 'Review', 'Done'],
        ],
        'content_calendar' => [
            'label' => 'Content calendar',
            'columns' => ['Ideas', 'Writing', 'Editing', 'Scheduled', 'Published'],
        ],
        'client_onboarding' => [
            'label' => 'Client onboarding',
            'columns' => ['New Client', 'Kickoff', 'In Progress', 'Review', 'Complete'],
        ],
    ];

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::TEMPLATES);
    }

    /**
     * Create the template's columns on a freshly-created (column-less) board.
     */
    public static function apply(Board $board, string $key): void
    {
        foreach (self::TEMPLATES[$key]['columns'] ?? [] as $position => $name) {
            $board->columns()->create(['name' => $name, 'position' => $position]);
        }
    }
}
