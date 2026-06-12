<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            return $this->response([
                'status' => 'ok',
                'database' => 'reachable',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->response([
                'status' => 'error',
                'database' => 'unreachable',
            ], 503);
        }
    }

    /**
     * @param  array{status: string, database: string}  $payload
     */
    private function response(array $payload, int $status = 200): JsonResponse
    {
        return response()
            ->json($payload, $status)
            ->header('Cache-Control', 'no-store, private');
    }
}
