<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    protected function successResponse($data = null, string $message = 'Berhasil', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message = 'Terjadi kesalahan', int $code = 500, ?\Throwable $exception = null): JsonResponse
    {
        if ($exception) {
            Log::error("API Error: " . $message, [
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $code);
    }
}
