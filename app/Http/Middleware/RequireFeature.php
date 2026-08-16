<?php

namespace App\Http\Middleware;

use App\Exceptions\License\FeatureNotLicensedException;
use App\Services\License\EntitlementManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireFeature middleware.
 *
 * Usage in routes:
 *   ->middleware('require-feature:dialer')
 *   ->middleware('require-feature:export')
 *
 * Checks the signed license entitlement for the specified feature flag.
 * Fails closed if the entitlement cannot be verified.
 */
class RequireFeature
{
    public function __construct(
        private readonly EntitlementManager $entitlementManager,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        try {
            $this->entitlementManager->assertCanUse($feature);
            return $next($request);
        } catch (FeatureNotLicensedException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->userMessage(),
                ], 403);
            }
            abort(403, $e->userMessage());
        } catch (\Throwable) {
            // Fail closed on any unexpected error
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'License validation failed. Please contact Softtrill support.',
                ], 403);
            }
            abort(403, 'License validation failed. Please contact Softtrill support.');
        }
    }
}
