<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use App\Models\User;
use App\Support\StaffBearerAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->validated('email')));
        $password = (string) $request->validated('password');
        $portalType = (string) ($request->validated('portal_type') ?? 'customer');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        $role = (string) $user->orderra_role;

        if ($portalType === 'portal' && ! StaffBearerAuth::portalRoleAllows($role)) {
            throw ValidationException::withMessages([
                'email' => 'Customer accounts cannot access the restaurant portal.',
            ]);
        }

        $tokenName = $portalType === 'portal' ? 'orderra-portal' : 'orderra-customer';
        $plainTextToken = $user->createToken($tokenName, abilities: ['*'])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
                'redirect_to' => $this->resolveRedirectTo($role),
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = new User;
        $user->name = trim((string) $validated['name']);
        $user->email = strtolower(trim((string) $validated['email']));
        $user->password = (string) $validated['password'];
        $user->orderra_role = 'customer';
        $user->save();

        $plainTextToken = $user->createToken('orderra-customer', abilities: ['*'])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $plainTextToken,
                'token_type' => 'Bearer',
                'redirect_to' => '/',
                'user' => $this->userPayload($user),
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        $token?->delete();

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
                'redirect_to' => $this->resolveRedirectTo((string) $user->orderra_role),
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    /** @return array{id:int,name:string,email:string,orderra_role:string} */
    private function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'orderra_role' => (string) $user->orderra_role,
        ];
    }

    private function resolveRedirectTo(string $role): string
    {
        return match ($role) {
            'admin' => '/admin',
            'staff' => '/portal/staff',
            default => '/',
        };
    }
}
