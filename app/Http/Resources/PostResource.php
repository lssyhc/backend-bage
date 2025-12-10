<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'rating' => $this->rating,
            'total_likes' => $this->likes()->count(),
            'total_comments' => $this->comments()->count(),
            'is_liked' => $this->isLikedBy($request->user()),
            'latest_comments' => $this->comments()
                ->latest()
                ->take(3)
                ->with('user:id,username')
                ->get()
                ->map(fn($c) => [
                    'username' => $c->user->username,
                    'content' => $c->content
                ]),
            'media_url' => $this->media_url ? url(Storage::url($this->media_url)) : null,
            'created_at' => $this->created_at->diffForHumans(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'profile_picture' => $this->user->profile_picture,
            ],
            'location' => [
                'id' => $this->location->id,
                'name' => $this->location->name,
            ]
        ];
    }
}
