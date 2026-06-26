<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\StaffBearerAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

/**
 * Bearer Sanctum staff token OR demo X-ORDERra-Admin-Key (reference guard fallback).
 */
final class OrderraAdminAccess
{
    public function __construct(
        private readonly EnsureAdminReferenceAccess $referenceGuard,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (is_string($plain) && $plain !== '') {
            [$user, $failure] = StaffBearerAuth::resolve($request);

            if ($user !== null && $failure === null) {
                if ($this->requiresAdminRole($request) && (string) $user->orderra_role !== 'admin') {
                    return new JsonResponse([
                        'message' => 'This admin section is restricted to authorized admin users.',
                    ], 403);
                }

                return $next($request);
            }

            $status = ($failure ?? 'invalid_token') === 'forbidden_role' ? 403 : 401;

            return new JsonResponse([
                'message' => $failure === 'forbidden_role'
                    ? 'Role is not allowed for ORDERra operations.'
                    : 'Invalid bearer token.',
            ], $status);
        }

        return $this->referenceGuard->handle($request, $next);
    }

    private function requiresAdminRole(Request $request): bool
    {
        $adminOnlyPatterns = [
            'api/v1/admin/demo-scenarios',
            'api/v1/admin/payments*',
            'api/v1/admin/refunds*',
            'api/v1/admin/webhooks*',
            'api/v1/admin/audit-logs*',
            'api/v1/admin/notification-logs*',
            'api/v1/admin/branches*',
            'api/v1/admin/delivery-zones*',
            'api/v1/admin/tax-rules*',
            'api/v1/admin/fee-rules*',
            'api/v1/admin/menu-categories*',
            'api/v1/admin/menu-items*',
            'api/v1/admin/promos*',
            'api/v1/admin/modifier-groups*',
            'api/v1/simulation*',
            'api/v1/webhooks*',
        ];

        foreach ($adminOnlyPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
