<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Authenticate a user and create a personal access token.
     * Hardened against timing attacks and brute-force lockouts.
     *
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->first();

        // Timing-attack mitigation for missing or inactive users
        if (! $user || ! $user->is_active) {
            Hash::check(
                $request->string('password')->toString(),
                '$2y$12$e8Y6bFwWJ0d1dZvhz95wCeB76OaP58F9N79J2p3G81fX6yqO2mF9C'
            );

            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password tidak valid.',
                ],
            ]);
        }

        // Account lockout check
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $remainingMinutes = (int) ceil(now()->diffInMinutes($user->locked_until, false));
            $remainingMinutes = max(1, $remainingMinutes);

            throw ValidationException::withMessages([
                'email' => [
                    "Akun Anda terkunci sementara karena terlalu banyak percobaan login yang salah. Silakan coba lagi setelah {$remainingMinutes} menit.",
                ],
            ]);
        }

        // Check password
        if (! Hash::check(
            $request->string('password')->toString(),
            $user->password
        )) {
            $failedAttempts = ($user->failed_login_attempts ?? 0) + 1;
            $updatePayload = ['failed_login_attempts' => $failedAttempts];

            if ($failedAttempts >= 5) {
                $updatePayload['locked_until'] = now()->addMinutes(15);
            }

            $user->forceFill($updatePayload)->save();

            if ($failedAttempts >= 5) {
                throw ValidationException::withMessages([
                    'email' => [
                        'Akun Anda terkunci sementara karena terlalu banyak percobaan login yang salah. Silakan coba lagi setelah 15 menit.',
                    ],
                ]);
            }

            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password tidak valid.',
                ],
            ]);
        }

        // Successful authentication: reset failed attempts and lockout
        $deviceName = $request->string(
            'device_name',
            'sikomando-api'
        )->toString();

        $token = $user->createToken($deviceName)->plainTextToken;

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'must_change_password' => (bool) $user->must_change_password,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 'Login berhasil.');
    }

    /**
     * Get the authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');
        $user = $request->user()->load(['roles', 'organizations']);

        return ApiResponse::success(
            new UserResource($user),
            'Profil pengguna berhasil diambil.'
        );
    }

    /**
     * Invalidate the current personal access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return ApiResponse::success(null, 'Logout berhasil.');
    }

    /**
     * Invalidate all personal access tokens for the authenticated user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return ApiResponse::success(null, 'Seluruh sesi berhasil diakhiri.');
    }

    /**
     * List all active sessions / personal access tokens for the user.
     */
    public function sessions(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $sessions = $request->user()
            ->tokens()
            ->latest('last_used_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'is_current' => $token->id === $currentTokenId,
            ]);

        return ApiResponse::success($sessions, 'Daftar sesi aktif berhasil diambil.');
    }

    /**
     * Revoke a specific personal access token.
     */
    public function revokeSession(Request $request, string $tokenId): JsonResponse
    {
        $deleted = $request->user()
            ->tokens()
            ->where('id', $tokenId)
            ->delete();

        if (! $deleted) {
            return ApiResponse::notFound('Sesi tidak ditemukan atau sudah tidak aktif.');
        }

        return ApiResponse::success(null, 'Sesi berhasil dicabut.');
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'revoke_other_sessions' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'password_changed_at' => now(),
            'must_change_password' => false,
        ])->save();

        if (! empty($validated['revoke_other_sessions'])) {
            $currentTokenId = $user->currentAccessToken()?->id;
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }

        return ApiResponse::success(null, 'Password berhasil diperbarui.');
    }
}
