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

    public function index($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            $likers = $post->likes()->with('user')->paginate(20);

            // Map likes to users
            $users = $likers->getCollection()->map(function ($like) {
                return $like->user;
            });

            $paginatedUsers = new \Illuminate\Pagination\LengthAwarePaginator(
                $users,
                $likers->total(),
                $likers->perPage(),
                $likers->currentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );

            return \App\Http\Resources\UserResource::collection($paginatedUsers);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unggahan tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memuat daftar like.', 500, $e);
        }
    }

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

                if ($post->user_id !== $user->id) {
                    Notification::where('user_id', $post->user_id)
                        ->where('type', 'like')
                        ->whereJsonContains('data->liker_username', $user->username)
                        ->whereJsonContains('data->post_id', $post->id)
                        ->delete();
                }
            } else {
                $post->likes()->create(['user_id' => $user->id]);
                $message = 'Menyukai unggahan.';
                $liked = true;

                if ($post->user_id !== $user->id) {
                    $notificationExists = Notification::where('user_id', $post->user_id)
                        ->where('type', 'like')
                        ->whereJsonContains('data->liker_username', $user->username)
                        ->whereJsonContains('data->post_id', $post->id)
                        ->exists();

                    if (!$notificationExists) {
                        Notification::create([
                            'user_id' => $post->user_id,
                            'type' => 'like',
                            'data' => [
                                'liker_username' => $user->username,
                                'liker_avatar' => $user->profile_picture,
                                'post_id' => $post->id,
                                'location_id' => $post->location->id,
                                'location_name' => $post->location->name,
                                'rating' => $post->rating,
                                'message' => "{$user->username} menyukai unggahan Anda."
                            ]
                        ]);
                    }
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
