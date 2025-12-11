<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LikeController extends Controller
{
    public function togglePostLike(Request $request, $postId)
    {
        $post = Post::with('location')->findOrFail($postId);
        $user = $request->user();

        $existingLike = $post->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            $existingLike->delete();
            $message = 'Unliked';
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $message = 'Liked';
            $liked = true;

            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'type' => 'like',
                    'data' => [
                        'liker_username' => $user->username,
                        'post_id' => $post->id,
                        'location_name' => $post->location->name,
                        'rating' => $post->rating,
                        'message' => "{$user->username} menyukai ulasan Anda di {$post->location->name}."
                    ]
                ]);
            }
        }

        return response()->json([
            'message' => $message,
            'liked' => $liked,
            'total_likes' => $post->likes()->count()
        ]);
    }
}
