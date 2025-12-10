<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Requests\StorePostRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostController extends Controller
{
    public function store(StorePostRequest $request)
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat postingan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $post = Post::with(['user', 'location', 'likes', 'comments.user'])->findOrFail($id);
            return new PostResource($post);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Postingan tidak ditemukan'], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            if ($request->user()->id !== $post->user_id) {
                return response()->json([
                    'message' => 'Akses Ditolak. Anda bukan pemilik postingan ini.'
                ], 403);
            }

            if ($post->media_url && Storage::disk('public')->exists($post->media_url)) {
                Storage::disk('public')->delete($post->media_url);
            }

            $post->delete();
            return response()->json(['message' => 'Unggahan berhasil dihapus']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Postingan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server'], 500);
        }
    }
}
