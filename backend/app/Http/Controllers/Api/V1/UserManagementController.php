<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserManagementResource;
use App\Http\Responses\ApiResponse;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserManagementService $userService
    ) {}

    /**
     * Display a listing of users with filters.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with('roles')
            ->when($request->query('role'), function ($q, $role) {
                $q->whereHas('roles', fn ($rq) => $rq->where('code', $role));
            })
            ->when($request->has('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name');

        $users = $query->paginate($request->integer('per_page', 15));

        return ApiResponse::success([
            'data' => UserManagementResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 'Daftar pengguna berhasil diambil.');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = $this->userService->createUser(
            $request->validated(),
            $request->user()
        );

        return ApiResponse::created(
            new UserManagementResource($user),
            'Pengguna berhasil dibuat.'
        );
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        $user->load(['roles', 'organizations']);

        return ApiResponse::success(
            new UserManagementResource($user),
            'Detail pengguna berhasil diambil.'
        );
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $updated = $this->userService->updateUser(
            $user,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new UserManagementResource($updated),
            'Data pengguna berhasil diperbarui.'
        );
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(User $user, Request $request): JsonResponse
    {
        Gate::authorize('toggleActive', $user);

        $updated = $this->userService->toggleActive($user, $request->user());

        $statusMsg = $updated->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return ApiResponse::success(
            new UserManagementResource($updated),
            "Pengguna berhasil {$statusMsg}."
        );
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        Gate::authorize('manageRoles', User::class);

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,code'],
        ]);

        $updated = $this->userService->assignRole(
            $user,
            $request->input('role'),
            $request->user()
        );

        return ApiResponse::success(
            new UserManagementResource($updated),
            'Role berhasil ditambahkan ke pengguna.'
        );
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        Gate::authorize('manageRoles', User::class);

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,code'],
        ]);

        $updated = $this->userService->removeRole(
            $user,
            $request->input('role'),
            $request->user()
        );

        return ApiResponse::success(
            new UserManagementResource($updated),
            'Role berhasil dicabut dari pengguna.'
        );
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        Gate::authorize('resetPassword', $user);

        $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $this->userService->resetPassword(
            $user,
            $request->input('password'),
            $request->user()
        );

        return ApiResponse::success(
            null,
            'Password pengguna berhasil direset.'
        );
    }

    /**
     * List active users by role for assignment dropdowns.
     */
    public function listByRole(string $roleCode): JsonResponse
    {
        Gate::authorize('listByRole', User::class);

        $users = User::where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('code', $roleCode))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return ApiResponse::success(
            $users,
            "Daftar pengguna aktif dengan role {$roleCode} berhasil diambil."
        );
    }
}
