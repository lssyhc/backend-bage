<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Requests\StorePostRequest;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $path = null;
        $type = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->store('posts', 'public');

            $mime = $file->getMimeType();
            $type = str_starts_with($mime, 'video') ? 'video' : 'image';
        }

        $post = Post::create([
            'user_id' => $request->user()->id,
            'location_id' => $validated['location_id'],
            'content' => $validated['content'],
            'rating' => $validated['rating'] ?? null,
            'media_url' => $path,
            'media_type' => $type,
        ]);

        return new PostResource($post->load(['user', 'location']));
    }

    public function destroy(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();
        return response()->json(['message' => 'Unggahan berhasil dihapus']);
    }
}
