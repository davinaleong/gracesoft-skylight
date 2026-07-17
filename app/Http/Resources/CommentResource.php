<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'card_id' => $this->card_id,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name,
            ],
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }
}
