<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Check overall system health across database, cache, and storage.
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $services = [];
        $httpCode = 200;

        // 1. Database Check
        try {
            $dbStart = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);

            $services['database'] = [
                'status' => 'up',
                'latency_ms' => $dbLatency,
            ];
        } catch (Throwable $e) {
            $status = 'unhealthy';
            $httpCode = 503;
            $services['database'] = [
                'status' => 'down',
                'message' => 'Layanan database tidak dapat diakses.',
            ];
        }

        // 2. Cache Check
        try {
            $cacheKey = 'health_check_ping';
            $cacheValue = 'ok_'.time();
            Cache::put($cacheKey, $cacheValue, 5);
            $retrieved = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            $services['cache'] = [
                'status' => $retrieved === $cacheValue ? 'up' : 'degraded',
            ];
        } catch (Throwable $e) {
            $status = 'degraded';
            $services['cache'] = [
                'status' => 'down',
                'message' => 'Layanan cache mengalami kendala.',
            ];
        }

        // 3. Storage Check
        try {
            $testFile = 'health_check_test_'.time().'.tmp';
            Storage::disk('local')->put($testFile, 'test');
            $canRead = Storage::disk('local')->exists($testFile);
            Storage::disk('local')->delete($testFile);

            $services['storage'] = [
                'status' => $canRead ? 'up' : 'degraded',
            ];
        } catch (Throwable $e) {
            $status = 'unhealthy';
            $httpCode = 503;
            $services['storage'] = [
                'status' => 'down',
                'message' => 'Layanan storage tidak dapat menulis atau membaca berkas.',
            ];
        }

        $data = [
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'services' => $services,
        ];

        return ApiResponse::success(
            data: $data,
            message: $status === 'healthy' ? 'Sistem beroperasi dengan normal.' : 'Sebagian layanan mengalami kendala.',
            status: $httpCode
        );
    }
}
