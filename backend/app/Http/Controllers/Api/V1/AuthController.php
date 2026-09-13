<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = \App\Models\User::query()
            ->where('email', $request->string('email')->toString())
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check(
            $request->string('password')->toString(),
            $user->password
        )) {
            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password tidak valid.',
                ],
            ]);
        }

        $deviceName = $request->string(
            'device_name',
            'sikomando-api'
        )->toString();

        $token = $user->createToken($deviceName)->plainTextToken;

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Profil pengguna berhasil diambil.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->roles->pluck('code')->values(),
            ],
        ]);
    }

public function logout(Request $request): JsonResponse
{
    $user = $request->user();

    $token = $user?->currentAccessToken();

    if ($token instanceof PersonalAccessToken) {
        $token->delete();
    }

    return response()->json([
        'success' => true,
        'message' => 'Logout berhasil.',
    ]);
}
}