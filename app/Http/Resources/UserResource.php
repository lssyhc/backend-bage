<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'bio' => $this->bio,
            'profile_picture_url' => $this->profile_picture
                ? url(Storage::url($this->profile_picture))
                : null,
            'is_followed' => $request->user() ? $request->user()->isFollowing($this->resource) : false,
            'joined_at' => $this->created_at->format('d M Y'),
            'stats' => [
                'locations_count' => $this->locations()->count(),
                'posts_count' => $this->posts()->count(),
                'followers_count' => $this->followers()->count(),
                'following_count' => $this->followings()->count(),
            ]
        ];
    }
}
