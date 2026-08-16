<?php

namespace App\Exceptions\License;

class LicenseTamperedException extends LicenseException
{
    public function __construct(string $message = 'License payload integrity check failed.')
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return 'License validation failed. Please contact Softtrill support.';
    }
}
