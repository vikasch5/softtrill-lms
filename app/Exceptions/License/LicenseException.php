<?php

namespace App\Exceptions\License;

use RuntimeException;

/**
 * Base class for all Softtrill license exceptions.
 * Never expose internal details to end users — use generic messages in UI.
 */
abstract class LicenseException extends RuntimeException
{
    /**
     * A safe message to show to end users (no internal details).
     */
    public function userMessage(): string
    {
        return 'License validation failed. Please contact Softtrill support.';
    }
}
