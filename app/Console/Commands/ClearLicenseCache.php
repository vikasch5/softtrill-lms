<?php

namespace App\Console\Commands;

use App\Services\License\LicenseManager;
use Illuminate\Console\Command;

class ClearLicenseCache extends Command
{
    protected $signature = 'license:clear-cache';
    protected $description = 'Clear the cached license validation status so the next request re-verifies immediately.';

    public function __construct(private readonly LicenseManager $licenseManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->licenseManager->clearCache();
        $this->info('License cache cleared. The next request will re-verify the signed entitlement.');
        $this->line('To force a full server re-validation, run: php artisan softtrill:license:refresh');
        return Command::SUCCESS;
    }
}
