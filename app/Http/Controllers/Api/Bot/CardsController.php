<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardsController extends Controller
{
    public function due(Request $request): JsonResponse
    {
        $user = $request->user();

        $dueToday = Card::dueToday($user)->with('column.board')->orderBy('ends_at')->get();
        $overdue = Card::overdue($user)->with('column.board')->orderBy('ends_at')->get();

        return response()->json([
            'date' => now()->toDateString(),
            'due_today' => $dueToday->map(fn (Card $card): array => $this->formatCard($card))->values(),
            'overdue' => $overdue->map(fn (Card $card): array => $this->formatCard($card))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCard(Card $card): array
    {
        return [
            'title' => $card->title,
            'board' => $card->column->board->name,
            'column' => $card->column->name,
            'ends_at' => $card->ends_at?->toDateTimeString(),
        ];
    }
}
