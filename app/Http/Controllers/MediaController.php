<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        $path = ltrim($path, '/');

        abort_if($path === '' || str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        $stream = Storage::disk('public')->readStream($path);
        abort_if($stream === false, 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
        ]);
    }
}
