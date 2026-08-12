<?php

namespace App\Exceptions\License;

class LicenseActivationException extends LicenseException
{
    public function __construct(string $message = 'License activation failed.')
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return 'License activation failed. Please contact Softtrill support.';
    }
}
