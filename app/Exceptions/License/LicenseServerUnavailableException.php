<?php

namespace App\Exceptions\License;

class LicenseServerUnavailableException extends LicenseException
{
    public function __construct(string $message = 'License server is unreachable.')
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return 'License server is temporarily unreachable. Please try again later.';
    }
}
