<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\User;
use App\Support\StaffBearerAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AdminAuthController extends Controller
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->validated('email')));
        $password = (string) $request->validated('password');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if (! StaffBearerAuth::portalRoleAllows((string) $user->orderra_role)) {
            throw ValidationException::withMessages([
                'email' => 'This account is not allowed to access ORDERra operations.',
            ]);
        }

        $plainTextToken = $user->createToken('orderra-admin-panel', abilities: ['*'])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->orderra_role,
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        StaffBearerAuth::deleteCurrentBearer($request);

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->orderra_role,
            ],
        ]);
    }
}
