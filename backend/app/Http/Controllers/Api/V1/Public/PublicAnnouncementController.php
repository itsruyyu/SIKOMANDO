<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\PublicAnnouncementFilterRequest;
use App\Http\Resources\Api\V1\Public\PublicAnnouncementResource;
use App\Models\Announcement;
use App\Services\PublicPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicAnnouncementController extends Controller
{
    public function __construct(
        protected PublicPortalService $publicPortalService
    ) {}

    /**
     * Display a listing of published public announcements.
     */
    public function index(PublicAnnouncementFilterRequest $request): AnonymousResourceCollection
    {
        $announcements = $this->publicPortalService->getPublicAnnouncements(
            filters: $request->validated(),
            perPage: (int) $request->input('per_page', 15)
        );

        return PublicAnnouncementResource::collection($announcements)
            ->additional([
                'success' => true,
                'message' => 'Daftar pengumuman publik berhasil diambil.',
            ]);
    }

    /**
     * Display the specified published announcement.
     */
    public function show(Announcement $announcement): JsonResponse
    {
        $item = $this->publicPortalService->getPublicAnnouncement($announcement);

        return (new PublicAnnouncementResource($item))
            ->additional([
                'success' => true,
                'message' => 'Detail pengumuman berhasil diambil.',
            ])
            ->response();
    }
}

