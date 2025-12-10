<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::findOrFail($postId);

        $comments = $post->comments()
            ->with('user')
            ->latest()
            ->paginate(10);

        return CommentResource::collection($comments);
    }

    public function store(Request $request, $postId)
    {
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
            'data' => $comment->load('user:id,name,username,profile_picture')
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        if ($request->user()->id !== $comment->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Komentar dihapus']);
    }
}
