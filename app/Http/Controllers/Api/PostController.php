<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Requests\StorePostRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostController extends Controller
{
    use ApiResponse;

    public function store(StorePostRequest $request)
    {
        try {
            $validated = $request->validated();
            $path = null;
            $type = null;

            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $path = $file->store('posts', 'public');

                if (!$path) {
                    throw new \Exception("File upload failed");
                }

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

            return $this->successResponse(
                new PostResource($post->load(['user', 'location'])),
                'Unggahan berhasil dipublikasikan.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membuat unggahan. Silakan coba lagi nanti.', 500, $e);
        }
    }

    public function show($id)
    {
        try {
            $post = Post::with(['user', 'location', 'likes', 'comments.user'])->findOrFail($id);
            return $this->successResponse(new PostResource($post), 'Detail unggahan ditemukan.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unggahan yang Anda cari tidak ditemukan atau sudah dihapus.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memuat unggahan.', 500, $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            if ($request->user()->id !== $post->user_id) {
                return $this->errorResponse('Anda tidak memiliki izin untuk menghapus unggahan ini.', 403);
            }

            if ($post->media_url && Storage::disk('public')->exists($post->media_url)) {
                Storage::disk('public')->delete($post->media_url);
            }

            $post->delete();

            return $this->successResponse(null, 'Unggahan berhasil dihapus.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Unggahan tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat menghapus unggahan.', 500, $e);
        }
    }
}
