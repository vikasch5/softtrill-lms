<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get the license key from config/environment
        $licenseKey = config('app.license_key', env('APP_LICENSE_KEY'));

        // 2. Perform validation (This is a basic example)
        // In a real scenario, you would decode a JWT, verify a signature, 
        // or make a curl request to your central license server.
        
        if (empty($licenseKey)) {
            // Log the attempt and block access
            Log::warning('License verification failed: No license key provided.');
            abort(403, 'Application License is Missing. Please contact support.');
        }

        // Example offline validation logic:
        // We expect the license key to be a specific format or match a hash.
        // For demonstration, we just require it to be 'VALID-OFFLINE-LICENSE'.
        // In production, use RSA public/private keys to verify a signed license file.
        if ($licenseKey !== 'VALID-OFFLINE-LICENSE') {
            Log::warning('License verification failed: Invalid license key.');
            abort(403, 'Application License is Invalid or Expired. Please contact support.');
        }

        // 3. Hardware Binding: Tie the license to the physical machine
        // Get the MAC address of the server
        $macAddress = $this->getServerMacAddress();
        
        // In a real application, the $licenseKey would contain a hash of the MAC address
        // For demonstration, we'll check if the license key contains 'HW-BOUND' 
        // and we will log the MAC address so you know what to hash later.
        if (str_contains($licenseKey, 'HW-BOUND')) {
            $expectedHash = md5($macAddress . 'your-secret-salt');
            // If the license key isn't formatted as HW-BOUND-md5hash...
            if ($licenseKey !== 'HW-BOUND-' . $expectedHash) {
                Log::warning("Hardware mismatch. Detected MAC: {$macAddress}, Hash: {$expectedHash}");
                abort(403, 'Application License is invalid for this hardware. Please contact support.');
            }
        }

        return $next($request);
    }

    /**
     * Attempts to retrieve the physical MAC address of the server
     */
    private function getServerMacAddress(): string
    {
        ob_start();
        system('getmac'); // Works on Windows
        $output = ob_get_clean();
        
        // Extract the first MAC address found in the output
        if (preg_match('/([0-9A-F]{2}[:-]){5}([0-9A-F]{2})/i', $output, $matches)) {
            return $matches[0];
        }
        
        // Fallback for Linux (if deployed there later)
        if (function_exists('exec')) {
            $linuxOutput = exec('cat /sys/class/net/*/address | head -n 1');
            if (!empty($linuxOutput)) {
                return trim($linuxOutput);
            }
        }

        return 'UNKNOWN-MAC';
    }
}
