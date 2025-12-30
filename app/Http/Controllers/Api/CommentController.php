<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Comment;
use App\Traits\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentController extends Controller
{
    use ApiResponse;

    public function index($postId)
    {
        try {
            $post = Post::findOrFail($postId);

            $comments = $post->comments()
                ->with('user')
                ->latest()
                ->paginate(20);

            return $this->successResponse(CommentResource::collection($comments), 'Komentar berhasil dimuat.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unggahan tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memuat komentar.', 500, $e);
        }
    }

    public function store(Request $request, $postId)
    {
        try {
            $request->validate(
                ['content' => 'required|string|max:500'],
                ['content.required' => 'Isi komentar tidak boleh kosong.']
            );

            $post = Post::with('location')->findOrFail($postId);

            $comment = $post->comments()->create([
                'user_id' => $request->user()->id,
                'content' => $request->input('content'),
            ]);

            if ($post->user_id !== $request->user()->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'type' => 'comment',
                    'data' => [
                        'commenter_username' => $request->user()->username,
                        'commenter_avatar' => $request->user()->profile_picture,
                        'post_id' => $post->id,
                        'location_id' => $post->location->id,
                        'location_name' => $post->location->name,
                        'rating' => $post->rating,
                        'comment_id' => $comment->id,
                        'comment_content' => $request->input('content'),
                        'message' => "{$request->user()->username} mengomentari unggahan Anda."
                    ]
                ]);
            }

            return $this->successResponse(
                new CommentResource($comment->load('user')),
                'Komentar berhasil dikirim.',
                201
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unggahan target tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengirim komentar.', 500, $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $comment = Comment::with('post')->findOrFail($id);

            if ($request->user()->id !== $comment->user_id) {
                return $this->errorResponse('Anda tidak memiliki izin menghapus komentar ini.', 403);
            }

            $post = $comment->post;
            $comment->delete();

            if ($post && $post->user_id !== $request->user()->id) {
                Notification::where('user_id', $post->user_id)
                    ->where('type', 'comment')
                    ->whereJsonContains('data->comment_id', $comment->id)
                    ->delete();
            }

            return $this->successResponse(null, 'Komentar berhasil dihapus.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Komentar tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus komentar.', 500, $e);
        }
    }
}
