<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(
            max((int) $request->integer('per_page', 15), 1),
            100
        );

        $unreadOnly = $request->boolean('unread_only');

        $notifications = $this->notificationService->getForUser(
            user: $request->user(),
            perPage: $perPage,
            unreadOnly: $unreadOnly,
        );

        return NotificationResource::collection($notifications)
            ->additional([
                'success' => true,
                'message' => 'Daftar notifikasi berhasil diambil.',
            ]);
    }

    /**
     * Get the count of unread notifications for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'unread_count' => $this->notificationService->unreadCount(
                $request->user()
            ),
        ], 'Jumlah notifikasi belum dibaca berhasil diambil.');
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(
        Request $request,
        Notification $notification,
    ): JsonResponse {
        $notification = $this->notificationService->markAsRead(
            notification: $notification,
            user: $request->user(),
        );

        return ApiResponse::success(
            new NotificationResource($notification),
            'Notifikasi berhasil ditandai sebagai dibaca.'
        );
    }

    /**
     * Mark all notifications for the authenticated user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $this->notificationService->markAllAsRead(
            $request->user()
        );

        return ApiResponse::success([
            'updated_count' => $updated,
        ], 'Seluruh notifikasi berhasil ditandai sebagai dibaca.');
    }
}
