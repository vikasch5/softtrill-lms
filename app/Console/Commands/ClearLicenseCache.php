<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class ClearLicenseCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cached license status so the next request re-verifies with softtrill.com immediately.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        LicenseService::clearCache();
        $this->info('License cache cleared. The next request will re-verify with softtrill.com.');
        return Command::SUCCESS;
    }
}
