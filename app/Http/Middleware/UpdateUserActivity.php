<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Throttle updates to once every 1 minute to prevent DB hammering
            $lastActivity = $user->last_activity_at ? \Carbon\Carbon::parse($user->last_activity_at) : null;
            $wasOffline = !$lastActivity || $lastActivity->diffInMinutes(now()) >= 1;
            
            if ($wasOffline) {
                // Use DB facade to avoid triggering eloquent events like updated_at on the whole model
                \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $user->id)
                    ->update(['last_activity_at' => now()]);
            }
            
            // Periodically send a heartbeat to the license server (every 1 minute)
            // This ensures the license server knows the LMS is active even if no one logs in/out.
            // Bypass the cache if the user just came online from being offline.
            if ($wasOffline || !\Illuminate\Support\Facades\Cache::has('license_heartbeat_sent')) {
                \Illuminate\Support\Facades\Cache::put('license_heartbeat_sent', true, 60);
                app(\App\Services\License\LicenseManager::class)->sendHeartbeat();
            }
        }

        return $next($request);
    }
}
