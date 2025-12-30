<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at->toIso8601String(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'profile_picture_url' => $this->user->profile_picture
                    ? url(Storage::url($this->user->profile_picture))
                    : null,
            ],
            'is_owner' => $request->user() ? $request->user()->id === $this->user_id : false,
        ];
    }
}
