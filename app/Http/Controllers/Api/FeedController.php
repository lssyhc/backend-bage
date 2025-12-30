<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type', 'fyp');

        $query = Post::with(['user', 'location', 'likes', 'comments.user']);

        if ($type === 'following') {
            $followingIds = $user->followings()->pluck('users.id');

            $query->whereIn('user_id', $followingIds)
                ->latest();
        } else {
            $query->withScoring();
        }

        return PostResource::collection($query->paginate(10));
    }

    public function search(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'latest');
        $type = $request->query('type'); // Add type parameter

        $query = Post::with(['user', 'location', 'likes', 'comments'])
            ->where('content', 'ilike', '%' . $search . '%');

        // Handle media filtering
        if ($type === 'media') {
            $query->whereHas('media');
        }

        if ($sort === 'top') {
            $query->withScoring(1.2);
        } else {
            $query->latest();
        }

        return PostResource::collection($query->paginate(10));
    }
}
