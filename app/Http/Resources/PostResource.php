<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $media = $this->media->map(fn ($m) => [
            'type' => $m->media_type,
            'url' => url(Storage::disk('public')->url($m->media_url)),
        ]);

        if ($this->media_url && ! $this->media->contains('media_url', $this->media_url)) {
            $media->push([
                'type' => $this->media_type ?? 'image',
                'url' => url(Storage::disk('public')->url($this->media_url)),
            ]);
        }

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
                ->with('user:id,username,name')
                ->get()
                ->map(fn ($c) => [
                    'username' => $c->user->username,
                    'name' => $c->user->name,
                    'content' => $c->content,
                ]),
            'media' => $media->values(),
            'created_at' => $this->created_at->toIso8601String(),
            'is_mine' => $request->user() ? $request->user()->id === $this->user_id : false,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'profile_picture_url' => $this->user->profile_picture
                    ? url(Storage::disk('public')->url($this->user->profile_picture))
                    : null,
                'is_followed' => $request->user() ? $request->user()->isFollowing($this->user) : false,
            ],
            'location' => [
                'id' => $this->location->id,
                'name' => $this->location->name,
            ],
        ];
    }
}
