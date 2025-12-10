<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Comment;
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
                ->paginate(10);

            return CommentResource::collection($comments);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Postingan tidak ditemukan'], 404);
        }
    }

    public function store(Request $request, $postId)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:500',
            ]);

            $post = Post::findOrFail($postId);

            $comment = $post->comments()->create([
                'user_id' => $request->user()->id,
                'content' => $request->content,
            ]);

            return response()->json([
                'message' => 'Komentar berhasil ditambahkan',
                'data' => new CommentResource($comment->load('user'))
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Postingan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambahkan komentar'], 500);
        }
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
