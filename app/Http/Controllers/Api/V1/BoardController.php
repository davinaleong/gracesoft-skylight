<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\AuthorizesWorkspaceEditing;
use App\Http\Controllers\Controller;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Services\PlanLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    use AuthorizesWorkspaceEditing;

    public function index(Request $request): JsonResponse
    {
        $boards = $request->user()->boards()->orderBy('position')->get();

        return BoardResource::collection($boards)->response();
    }

    public function show(Board $board): JsonResponse
    {
        $this->authorizeView($board);

        return (new BoardResource($board->load('columns.cards')))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace();

        abort_unless($workspace?->canEditContent($request->user()), 403);

        if (! PlanLimiter::canCreateBoard($workspace)) {
            return response()->json([
                'message' => 'You\'ve reached your plan\'s board limit ('.PlanLimiter::boardLimit($workspace).').',
            ], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $board = $request->user()->boards()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'position' => $request->user()->boards()->count(),
        ]);

        return (new BoardResource($board))->response()->setStatusCode(201);
    }

    public function update(Request $request, Board $board): JsonResponse
    {
        $this->authorizeEdit($board);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $board->update($data);

        return (new BoardResource($board))->response();
    }

    public function destroy(Board $board): JsonResponse
    {
        $this->authorizeEdit($board);

        $board->delete();

        return response()->json(null, 204);
    }
}
