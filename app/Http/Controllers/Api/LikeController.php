<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Traits\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LikeController extends Controller
{
    use ApiResponse;

    public function togglePostLike(Request $request, $postId)
    {
        try {
            $post = Post::with('location')->findOrFail($postId);
            $user = $request->user();

            $existingLike = $post->likes()->where('user_id', $user->id)->first();
            $liked = false;
            $message = '';

            if ($existingLike) {
                $existingLike->delete();
                $message = 'Batal menyukai.';
                $liked = false;
            } else {
                $post->likes()->create(['user_id' => $user->id]);
                $message = 'Menyukai unggahan.';
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

            return $this->successResponse([
                'liked' => $liked,
                'total_likes' => $post->likes()->count()
            ], $message);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unggahan tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses like.', 500, $e);
        }
    }
}
