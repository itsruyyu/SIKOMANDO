<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
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
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function unauthorized(
        string $message = 'Unauthenticated.'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: 401
        );
    }

    public static function forbidden(
        string $message = 'Forbidden.'
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
        return response()->json(null, 204);
    }
}
