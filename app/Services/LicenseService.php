<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LicenseService
{
    /**
     * How long (in seconds) to cache a successful license response.
     * 300 = 5 minutes. Status changes on softtrill.com take effect within this window.
     */
    private const CACHE_TTL = 300;

    /**
     * Fail-open grace period when softtrill.com is unreachable (24 hours).
     */
    private const GRACE_TTL = 86400;

    private const CACHE_KEY = 'license_status';

    /**
     * Check the license status against softtrill.com.
     *
     * Returns: 'active' | 'paused' | 'expired' | 'not_found' | 'unreachable'
     */
    public static function check(): string
    {
        // Return cached result if still fresh (avoids pinging on every request)
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        $installationId = self::getOrCreateInstallationId();
        $domain = config('app.url');
        $fingerprint = self::buildFingerprint($installationId, $domain);
        $licenseServer = rtrim(config('license.server_url', 'https://softtrill.com'), '/');

        try {
            $licenseKey = self::getLicenseKey();

            if (empty($licenseKey)) {
                Log::warning('License key not found.');
                return 'not_found';
            }

            $response = Http::timeout(8)
                ->acceptJson()
                ->post("{$licenseServer}/api/license/verify", [
                    'license_key' => $licenseKey,
                    'installation_id' => $installationId,
                    'domain' => $domain,
                    'fingerprint' => $fingerprint,
                ]);

            if ($response->successful()) {
                $status = $response->json('status', 'not_found');

                if (!in_array($status, ['active', 'paused', 'expired', 'not_found'])) {
                    $status = 'not_found';
                }

                Log::info("License check OK: status={$status}", [
                    'installation_id' => $installationId,
                    'domain' => $domain,
                ]);

                // Cache the live result
                Cache::put(self::CACHE_KEY, $status, self::CACHE_TTL);
                // Also store a longer-lived grace copy
                Cache::put(self::CACHE_KEY . '_grace', $status, self::GRACE_TTL);

                return $status;
            }

            Log::warning('License server non-200 response.', [
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::warning('License server unreachable: ' . $e->getMessage());
        }

        // --- Fail-open: use grace cache if server is unreachable ---
        if (Cache::has(self::CACHE_KEY . '_grace')) {
            $gracedStatus = Cache::get(self::CACHE_KEY . '_grace');
            Log::warning("Using grace-period cached license status: {$gracedStatus}");
            // Short re-cache to avoid hammering the server every request
            Cache::put(self::CACHE_KEY, $gracedStatus, 300);
            return $gracedStatus;
        }

        // No cache + server down = block
        return 'unreachable';
    }

    /**
     * Returns (or creates on first boot) the hidden installation UUID.
     * This ID is stored in the DB only, never shown to the client.
     */
    public static function getOrCreateInstallationId(): string
    {
        $row = DB::table('settings')->where('key', 'installation_id')->first();

        if ($row) {
            return $row->value;
        }

        $newId = Str::uuid()->toString();

        DB::table('settings')->insert([
            'key' => 'installation_id',
            'value' => $newId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("First boot: installation_id created. Register this on softtrill.com: {$newId}");

        return $newId;
    }

    /**
     * SHA-256 fingerprint = hash(installation_id + domain + MAC + salt).
     * softtrill.com recomputes this to confirm the request is genuine.
     */
    private static function buildFingerprint(string $installationId, string $domain): string
    {
        $mac = self::getServerMacAddress();
        $salt = config('license.secret_salt', 'change-this-salt-in-env');

        return hash('sha256', implode('|', [$installationId, $domain, $mac, $salt]));
    }

    /**
     * Cross-platform MAC address retrieval.
     */
    private static function getServerMacAddress(): string
    {
        // Windows
        if (PHP_OS_FAMILY === 'Windows') {
            ob_start();
            @system('getmac 2>nul');
            $output = ob_get_clean();
            if (preg_match('/([0-9A-F]{2}[:-]){5}([0-9A-F]{2})/i', $output, $matches)) {
                return strtolower($matches[0]);
            }
        }

        // Linux / Unix
        if (function_exists('exec')) {
            $out = @exec('cat /sys/class/net/eth0/address 2>/dev/null');
            if (!empty($out)) {
                return trim($out);
            }
            $out = @exec("ip link 2>/dev/null | grep -oE '([0-9a-f]{2}:){5}[0-9a-f]{2}' | head -n 1");
            if (!empty($out)) {
                return trim($out);
            }
        }

        // Fallback: hostname is still unique per machine
        return gethostname() ?: 'UNKNOWN-HOST';
    }

    /**
     * Clear the license cache (call this from an Artisan command or webhook
     * after you change status in the softtrill.com panel).
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY . '_grace');
    }

    public static function getLicenseKey(): ?string
    {
        $row = DB::table('settings')
            ->where('key', 'license_key')
            ->first();

        return $row?->value;
    }

    public static function saveLicenseKey(string $licenseKey): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'license_key'],
            [
                'value' => trim($licenseKey),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        self::clearCache();
    }

    public static function hasLicenseKey(): bool
    {
        return !empty(self::getLicenseKey());
    }
}
