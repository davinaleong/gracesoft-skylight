<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Column;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $boards = $request->user()->boards()
            ->with(['columns' => fn ($query) => $query->withCount('cards')])
            ->get()
            ->map(fn (Board $board): array => [
                'board' => $board->name,
                'columns' => $board->columns
                    ->map(fn (Column $column): array => [
                        'column' => $column->name,
                        'card_count' => $column->cards_count,
                    ])
                    ->values(),
            ])
            ->values();

        return response()->json(['boards' => $boards]);
    }
}
