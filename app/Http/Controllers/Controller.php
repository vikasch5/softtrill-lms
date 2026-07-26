<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;

abstract class Controller
{
    public function __construct()
    {
        // Application runtime check — required for core functionality
        if (!app()->runningInConsole()) {
            $s = LicenseService::check();
            if ($s !== 'active') {
                abort($s === 'unreachable' ? 503 : 403, 'Application license error. Contact Softtrill support.');
            }
        }
    }
}
