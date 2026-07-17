<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\AuthorizesWorkspaceEditing;
use App\Http\Controllers\Controller;
use App\Http\Resources\ColumnResource;
use App\Models\Board;
use App\Models\Column;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    use AuthorizesWorkspaceEditing;

    public function index(Board $board): JsonResponse
    {
        $this->authorizeView($board);

        return ColumnResource::collection($board->columns()->orderBy('position')->get())->response();
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $this->authorizeEdit($board);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $column = $board->columns()->create([
            'name' => $data['name'],
            'position' => $board->columns()->count(),
        ]);

        return (new ColumnResource($column))->response()->setStatusCode(201);
    }

    public function update(Request $request, Column $column): JsonResponse
    {
        $this->authorizeEdit($column);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $column->update($data);

        return (new ColumnResource($column))->response();
    }

    public function destroy(Column $column): JsonResponse
    {
        $this->authorizeEdit($column);

        $column->delete();

        return response()->json(null, 204);
    }
}
