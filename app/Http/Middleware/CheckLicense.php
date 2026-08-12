<?php

namespace App\Http\Middleware;

use App\Exceptions\License\LicenseExpiredException;
use App\Exceptions\License\LicenseRevokedException;
use App\Exceptions\License\LicenseServerUnavailableException;
use App\Exceptions\License\LicenseTamperedException;
use App\Services\License\LicenseManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckLicense middleware.
 *
 * This middleware enforces license validity using the cryptographically
 * verified entitlement managed by LicenseManager.
 *
 * Security notes:
 * - This middleware alone is NOT the only security boundary.
 * - AppServiceProvider::boot() also calls LicenseManager::boot()
 * - Removing this middleware does NOT bypass the service provider check
 * - Critical operations (user creation) have additional enforcement in controllers
 *
 * Fail-closed: if no valid entitlement exists, the request is denied.
 * Exception: requests on the exclusion list (login, health check, webhook).
 */
class CheckLicense
{
    /**
     * Routes that bypass license checking.
     * Keep this list minimal — every excluded route is a potential weakness.
     */
    private const EXCLUDED_ROUTES = [
        'login',
        'task.doLogin',
        'task.register',
        'register',
        'send.otp',
        'home',
        'logout',
    ];

    public function __construct(
        private readonly LicenseManager $licenseManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Skip excluded routes
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        try {
            // LicenseManager verifies the Ed25519 signature on every load
            // (fast path: cache flag prevents DB hit on most requests)
            $this->licenseManager->boot();
            return $next($request);

        } catch (LicenseExpiredException $e) {
            Log::warning('[CheckLicense] License expired.', ['ip' => $request->ip()]);
            return $this->denyResponse(403, 'Your application license has expired. Please renew at softtrill.com.');

        } catch (LicenseRevokedException $e) {
            Log::warning('[CheckLicense] License revoked/suspended.', ['ip' => $request->ip()]);
            return $this->denyResponse(403, 'Your application license has been suspended. Please contact Softtrill support.');

        } catch (LicenseTamperedException $e) {
            Log::critical('[CheckLicense] License tampered or invalid.', [
                'ip'    => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            // Generic message — do not expose why
            return $this->denyResponse(403, 'License validation failed. Please contact Softtrill support.');

        } catch (LicenseServerUnavailableException $e) {
            Log::warning('[CheckLicense] License server unavailable (grace expired).', ['ip' => $request->ip()]);
            return $this->denyResponse(503, 'License server unreachable. Please try again later or contact Softtrill support.');

        } catch (\Throwable $e) {
            Log::error('[CheckLicense] Unexpected license error.', ['error' => $e->getMessage()]);
            return $this->denyResponse(403, 'License validation failed. Please contact Softtrill support.');
        }
    }

    private function isExcluded(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, self::EXCLUDED_ROUTES, strict: true)) {
            return true;
        }
        // Health check endpoint
        if ($request->is('up')) {
            return true;
        }
        // License webhook (has its own HMAC verification)
        if ($request->is('license-webhook')) {
            return true;
        }
        return false;
    }

    private function denyResponse(int $status, string $message): Response
    {
        if (request()->expectsJson()) {
            return response()->json(['error' => $message], $status);
        }
        abort($status, $message);
    }
}
