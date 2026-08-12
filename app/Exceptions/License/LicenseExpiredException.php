<?php

namespace App\Exceptions\License;

class LicenseExpiredException extends LicenseException
{
    public function __construct(string $message = 'License has expired.')
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return 'Your application license has expired. Please renew at softtrill.com.';
    }
}
