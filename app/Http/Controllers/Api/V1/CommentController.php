<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\AuthorizesWorkspaceEditing;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Card;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use AuthorizesWorkspaceEditing;

    public function index(Card $card): JsonResponse
    {
        $this->authorizeView($card);

        return CommentResource::collection($card->comments()->with('user')->get())->response();
    }

    public function store(Request $request, Card $card): JsonResponse
    {
        $this->authorizeEdit($card);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $card->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return (new CommentResource($comment->load('user')))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->authorizeEdit($comment->card);

        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return response()->json(null, 204);
    }
}
