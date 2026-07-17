<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\AuthorizesWorkspaceEditing;
use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\Column;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CardController extends Controller
{
    use AuthorizesWorkspaceEditing;

    public function index(Column $column): JsonResponse
    {
        $this->authorizeView($column);

        $cards = $column->cards()->with('labels')->withCount('comments')->orderBy('position')->get();

        return CardResource::collection($cards)->response();
    }

    public function show(Card $card): JsonResponse
    {
        $this->authorizeView($card);

        return (new CardResource($card->loadMissing('labels')->loadCount('comments')))->response();
    }

    public function store(Request $request, Column $column): JsonResponse
    {
        $this->authorizeEdit($column);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', Rule::in(array_keys(Card::COLORS))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $card = $column->cards()->create([
            ...$data,
            'position' => $column->cards()->count(),
        ]);

        return (new CardResource($card))->response()->setStatusCode(201);
    }

    public function update(Request $request, Card $card): JsonResponse
    {
        $this->authorizeEdit($card);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'color' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(Card::COLORS))],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $card->update($data);

        return (new CardResource($card))->response();
    }

    public function destroy(Card $card): JsonResponse
    {
        $this->authorizeEdit($card);

        $card->delete();

        return response()->json(null, 204);
    }
}
