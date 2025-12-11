<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentController extends Controller
{
    public function index($postId)
    {
        try {
            $post = Post::findOrFail($postId);

            $comments = $post->comments()
                ->with('user')
                ->latest()
                ->paginate(20);

            return CommentResource::collection($comments);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Postingan tidak ditemukan'], 404);
        }
    }

    public function store(Request $request, $postId)
    {
        $request->validate(['content' => 'required|string|max:500']);
        $post = Post::with('location')->findOrFail($postId);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        if ($post->user_id !== $request->user()->id) {
            Notification::create([
                'user_id' => $post->user_id,
                'type' => 'comment',
                'data' => [
                    'commenter_username' => $request->user()->username,
                    'post_id' => $post->id,
                    'location_name' => $post->location->name,
                    'rating' => $post->rating,
                    'comment_content' => $request->content,
                    'message' => "{$request->user()->username} mengomentari: \"{$request->content}\""
                ]
            ]);
        }

        return response()->json([
            'message' => 'Komentar berhasil ditambahkan',
            'data' => new CommentResource($comment->load('user'))
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $comment = Comment::findOrFail($id);

            if ($request->user()->id !== $comment->user_id) {
                return response()->json([
                    'message' => 'Akses Ditolak. Anda bukan pemilik komentar ini.'
                ], 403);
            }

            $comment->delete();
            return response()->json(['message' => 'Komentar dihapus']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Komentar tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server'], 500);
        }
    }
}
