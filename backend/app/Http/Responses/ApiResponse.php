<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        ?string $resourceClass = null,
        string $message = 'Success'
    ): JsonResponse {
        $items = $resourceClass ? $resourceClass::collection($paginator->items()) : $paginator->items();

        return self::success([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], $message);
    }

    public static function created(
        mixed $data = null,
        string $message = 'Data berhasil dibuat'
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            status: 201
        );
    }

    public static function error(
        string $message = 'Terjadi kesalahan',
        int $status = 400,
        mixed $errors = null
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function unauthorized(
        string $message = 'Autentikasi diperlukan.'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 401
        );
    }

    public static function forbidden(
        string $message = 'Anda tidak memiliki izin untuk melakukan tindakan ini.'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 403
        );
    }

    public static function notFound(
        string $message = 'Data tidak ditemukan'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 404
        );
    }

    public static function validation(
        mixed $errors = null,
        string $message = 'Data yang diberikan tidak valid'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 422,
            errors: $errors
        );
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }
}
