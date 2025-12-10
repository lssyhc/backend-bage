<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LikeController extends Controller
{
    public function togglePostLike(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        $userId = $request->user()->id;
        $existingLike = $post->likes()->where('user_id', $userId)->first();

        if ($existingLike) {
            $existingLike->delete();
            $message = 'Unliked';
            $liked = false;
        } else {
            $post->likes()->create([
                'user_id' => $userId
            ]);
            $message = 'Liked';
            $liked = true;
        }

        return response()->json([
            'message' => $message,
            'liked' => $liked,
            'total_likes' => $post->likes()->count()
        ]);
    }
}
