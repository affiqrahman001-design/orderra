<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdminReferenceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('admin_reference.guard.enabled', true)) {
            return $next($request);
        }

        $headerName = (string) config('admin_reference.guard.header_name', 'X-ORDERra-Admin-Key');
        $expectedToken = trim((string) config('admin_reference.guard.token', ''));
        $providedToken = trim((string) $request->header($headerName, ''));

        if ($expectedToken === '') {
            return new JsonResponse([
                'message' => 'Admin reference guard is enabled but no admin token is configured.',
            ], 503);
        }

        if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return new JsonResponse([
                'message' => 'Admin reference access denied.',
            ], 403);
        }

        if (
            (bool) config('admin_reference.guard.readonly_mode', false)
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
        ) {
            return new JsonResponse([
                'message' => 'Admin reference is in read-only mode.',
            ], 423);
        }

        return $next($request);
    }
}
