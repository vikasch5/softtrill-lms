<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckLicense
{
    /**
     * Handle an incoming request.
     *
     * Verifies the license status with softtrill.com.
     * No license key is stored client-side — the installation is identified
     * by a hidden UUID in the database + a server fingerprint.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = LicenseService::check();

        return match ($status) {
            'active'      => $next($request),
            'paused'      => abort(403, 'Your application license has been paused. Please contact Softtrill support.'),
            'expired'     => abort(403, 'Your application license has expired. Please renew at softtrill.com.'),
            'not_found'   => abort(403, 'This installation is not registered. Please contact Softtrill support.'),
            'unreachable' => abort(503, 'License server could not be reached and no cached status is available. Please try again shortly.'),
            default       => abort(403, 'License verification failed. Please contact Softtrill support.'),
        };
    }
}

