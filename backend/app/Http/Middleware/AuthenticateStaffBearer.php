<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\StaffBearerAuth;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Login-only routes after auth: Bearer staff token required (no admin reference fallback). */
final class AuthenticateStaffBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! is_string($plain) || $plain === '') {
            return new JsonResponse([
                'message' => 'Bearer token required.',
            ], 401);
        }

        [$user, $failure] = StaffBearerAuth::resolve($request);

        if ($user !== null && $failure === null) {
            return $next($request);
        }

        if ($failure === 'forbidden_role') {
            return new JsonResponse([
                'message' => 'Role is not allowed for ORDERra operations.',
            ], 403);
        }

        return new JsonResponse([
            'message' => 'Invalid or expired bearer token.',
        ], 401);
    }
}
