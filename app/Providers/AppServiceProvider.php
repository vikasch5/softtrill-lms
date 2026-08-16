<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\Offer;
use App\Models\User;
use App\Models\UserDetails;
use App\Services\License\EntitlementManager;
use App\Services\License\InstallationManager;
use App\Services\License\LicenseClient;
use App\Services\License\LicenseManager;
use App\Services\License\LicenseVerifier;
use App\Services\License\TamperDetector;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind LicenseVerifier as a singleton — stateless, no side-effects
        $this->app->singleton(LicenseVerifier::class, function ($app) {
            return new LicenseVerifier(
                publicKeyBase64: config('license.public_key'),
                expectedKeyId: config('license.key_id'),
                expectedProduct: config('license.product'),
            );
        });

        // Bind LicenseClient as a singleton
        $this->app->singleton(LicenseClient::class, function ($app) {
            return new LicenseClient(
                serverUrl: config('license.server_url'),
                timeout: config('license.timeout', 11),
            );
        });

        // Bind InstallationManager — depends on LicenseVerifier
        $this->app->singleton(InstallationManager::class, function ($app) {
            return new InstallationManager(
                verifier: $app->make(LicenseVerifier::class),
            );
        });

        // Bind LicenseManager — orchestrator, depends on all three
        $this->app->singleton(LicenseManager::class, function ($app) {
            return new LicenseManager(
                verifier: $app->make(LicenseVerifier::class),
                client: $app->make(LicenseClient::class),
                installationManager: $app->make(InstallationManager::class),
            );
        });

        // Bind EntitlementManager — depends on LicenseManager
        $this->app->singleton(EntitlementManager::class, function ($app) {
            return new EntitlementManager(
                licenseManager: $app->make(LicenseManager::class),
            );
        });

        // Bind TamperDetector
        $this->app->singleton(TamperDetector::class, function ($app) {
            return new TamperDetector(
                verifier: $app->make(LicenseVerifier::class),
                manifestPath: config('license.manifest_path'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Core application integrity check — do not remove
        $this->verifyApplicationIntegrity();

        Paginator::useBootstrapFive();

        View::composer('lms.common.header', function ($view) {
            $stats = [
                'today' => 0,
                'pending' => 0,
                'upcoming' => 0,
            ];

            $user = Auth::user();

            if (!$user) {
                $view->with('headerFollowupStats', $stats);
                return;
            }

            $visibleUserIds = [$user->id];

            if ($user->hasRole('Manager')) {
                $teamLeaderIds = UserDetails::where('manager_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $agentIds = !empty($teamLeaderIds)
                    ? UserDetails::whereIn('teamleader_id', $teamLeaderIds)->pluck('user_id')->toArray()
                    : [];

                $directAgentIds = UserDetails::where('manager_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $visibleUserIds = array_merge($visibleUserIds, $teamLeaderIds, $agentIds, $directAgentIds);
            } elseif ($user->hasRole('Cluster')) {
                $clusterUserIds = UserDetails::where('cluster_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $visibleUserIds = array_merge($visibleUserIds, $clusterUserIds);
            } elseif ($user->hasRole('TeamLeader')) {
                $agentIds = UserDetails::where('teamleader_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();

                $visibleUserIds = array_merge($visibleUserIds, $agentIds);
            }

            $visibleUserIds = array_values(array_unique($visibleUserIds));
            $today = Carbon::today();

            $followupQuery = Lead::query()
                ->whereNotNull('next_followup_at');

            if (!$user->hasRole('Admin')) {
                $followupQuery->where(function ($query) use ($visibleUserIds) {
                    $query
                        ->whereIn('assigned_to', $visibleUserIds)
                        ->orWhereIn('added_by', $visibleUserIds);
                });
            }

            $stats['today'] = (clone $followupQuery)
                ->whereDate('next_followup_at', $today)
                ->count();

            $stats['pending'] = (clone $followupQuery)
                ->where('next_followup_at', '<', $today->copy()->startOfDay())
                ->count();

            $stats['upcoming'] = (clone $followupQuery)
                ->where('next_followup_at', '>', $today->copy()->endOfDay())
                ->count();

            // --- Offer Logic ---
            $activeOffers = collect();
            if ($user) {
                // Eager load details if not loaded to prevent N+1
                if (!$user->relationLoaded('details')) {
                    $user->load('details');
                }

                $allowedUserIds = [$user->id];
                $details = $user->details;

                if ($details) {
                    if ($details->teamleader_id)
                        $allowedUserIds[] = $details->teamleader_id;
                    if ($details->manager_id)
                        $allowedUserIds[] = $details->manager_id;
                    if ($details->cluster_id)
                        $allowedUserIds[] = $details->cluster_id;
                }

                $adminIds = User::role('admin')->pluck('id')->toArray();
                $allowedUserIds = array_unique(array_merge($allowedUserIds, $adminIds));

                $todayDate = $today->format('Y-m-d');
                $tenantId = $user->tenant_id ?? $user->id;

                $activeOffers = Offer::select(['id', 'heading', 'description', 'url', 'image'])
                    ->where('status', 1)
                    ->where('tenant_id', $tenantId)
                    ->whereIn('added_by', $allowedUserIds)
                    ->where(function ($q) use ($todayDate) {
                        $q
                            ->whereNull('start_date')
                            ->orWhere('start_date', '<=', $todayDate);
                    })
                    ->where(function ($q) use ($todayDate) {
                        $q
                            ->whereNull('end_date')
                            ->orWhere('end_date', '>=', $todayDate);
                    })
                    ->latest('id')
                    ->get();
            }

            $view->with('headerFollowupStats', $stats);
            $view->with('activeOffers', $activeOffers);
        });
    }

    /**
     * Verifies core application integrity on boot.
     *
     * This is a SECOND enforcement point (in addition to the CheckLicense middleware).
     * Having two independent enforcement points means a customer must bypass BOTH
     * the middleware AND the service provider to avoid license checks.
     *
     * Skipped in CLI context to allow migrations/commands to run normally.
     */
    private function verifyApplicationIntegrity(): void
    {
        if (app()->runningInConsole() || request()->is('license-webhook')) {
            return;
        }

        try {
            // Run Tamper Detection in production
            // It's recommended to skip this in local dev unless explicitly enabled
            if (!app()->environment('local') || env('ENABLE_TAMPER_DETECTION', false)) {
                $detector = app(\App\Services\License\TamperDetector::class);
                if (!$detector->checkIntegrity()) {
                    // Instantly crash if tampering is detected
                    throw new \App\Exceptions\License\LicenseTamperedException(
                        'Application integrity compromised. Please contact support.'
                    );
                }
            }

            // LicenseManager::boot() verifies the Ed25519-signed entitlement.
            // It uses a short-lived cache flag to avoid a DB hit on every request.
            app(LicenseManager::class)->boot();
        } catch (\App\Exceptions\License\LicenseExpiredException $e) {
            abort(403, $e->userMessage());
        } catch (\App\Exceptions\License\LicenseRevokedException $e) {
            abort(403, $e->userMessage());
        } catch (\App\Exceptions\License\LicenseServerUnavailableException $e) {
            abort(503, $e->userMessage());
        } catch (\App\Exceptions\License\LicenseTamperedException $e) {
            // Do not expose which check failed
            abort(403, 'License validation failed. Please contact Softtrill support.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[AppServiceProvider] License boot error: ' . $e->getMessage());
            abort(403, 'License validation failed. Please contact Softtrill support.');
        }
    }
}
