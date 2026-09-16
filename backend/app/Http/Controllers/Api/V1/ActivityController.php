<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    /**
     * Display a listing of audit log activities.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(
            max((int) $request->integer('per_page', 15), 1),
            100
        );

        $query = AuditLog::query()
            ->with([
                'actor:id,name',
            ])
            ->latest('occurred_at');

        if ($request->filled('module')) {
            $query->where(
                'module',
                $request->string('module')->toString()
            );
        }

        if ($request->filled('action')) {
            $query->where(
                'action',
                $request->string('action')->toString()
            );
        }

        if ($request->filled('entity_type')) {
            $query->where(
                'entity_type',
                $request->string('entity_type')->toString()
            );
        }

        if ($request->filled('actor_id')) {
            $query->where(
                'actor_id',
                $request->string('actor_id')->toString()
            );
        }

        return ActivityResource::collection(
            $query->paginate($perPage)->withQueryString()
        )->additional([
            'success' => true,
            'message' => 'Daftar aktivitas berhasil diambil.',
        ]);
    }
}
