<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class StaffBearerAuth
{
    /** @return array{0: User|null, 1: 'invalid_token'|'forbidden_role'|null} */
    public static function resolve(Request $request): array
    {
        $tokenPlain = $request->bearerToken();

        if (! is_string($tokenPlain) || $tokenPlain === '') {
            return [null, null];
        }

        $accessToken = PersonalAccessToken::findToken($tokenPlain);

        if ($accessToken === null) {
            return [null, 'invalid_token'];
        }

        $tokenable = $accessToken->tokenable;

        if (! $tokenable instanceof User) {
            return [null, 'invalid_token'];
        }

        $role = (string) $tokenable->orderra_role;

        if (! self::portalRoleAllows($role)) {
            return [null, 'forbidden_role'];
        }

        $request->setUserResolver(static fn (): User => $tokenable);

        return [$tokenable, null];
    }

    public static function portalRoleAllows(string $role): bool
    {
        $allowed = config('staff.portal_roles', []);

        return in_array($role, $allowed, true);
    }

    /** Delete the access token referenced by Authorization header when valid. */
    public static function deleteCurrentBearer(Request $request): bool
    {
        $tokenPlain = $request->bearerToken();

        if (! is_string($tokenPlain) || $tokenPlain === '') {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($tokenPlain);

        return (bool) $accessToken?->delete();
    }
}
