<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogDetailResource;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * List internal audit logs with search, filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $query = AuditLog::with('actor:id,name,email')
            ->when($request->query('module'), fn ($q, $v) => $q->where('module', $v))
            ->when($request->query('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->query('actor_id'), fn ($q, $v) => $q->where('actor_id', $v))
            ->when($request->query('entity_type'), fn ($q, $v) => $q->where('entity_type', $v))
            ->when($request->query('entity_id'), fn ($q, $v) => $q->where('entity_id', $v))
            ->when($request->query('start_date'), fn ($q, $v) => $q->whereDate('occurred_at', '>=', $v))
            ->when($request->query('end_date'), fn ($q, $v) => $q->whereDate('occurred_at', '<=', $v))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('action', 'ilike', "%{$search}%")
                        ->orWhere('module', 'ilike', "%{$search}%")
                        ->orWhere('request_id', 'ilike', "%{$search}%");
                });
            })
            ->latest('occurred_at');

        $paginated = $query->paginate($perPage);

        return ApiResponse::success([
            'data' => AuditLogDetailResource::collection($paginated),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ], 'Daftar audit log internal berhasil diambil.');
    }

    /**
     * Show a specific audit log detail.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        Gate::authorize('view', $auditLog);

        $auditLog->load('actor:id,name,email');

        return ApiResponse::success(
            new AuditLogDetailResource($auditLog),
            'Detail audit log berhasil diambil.'
        );
    }
}
