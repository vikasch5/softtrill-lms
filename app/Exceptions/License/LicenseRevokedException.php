<?php

namespace App\Exceptions\License;

class LicenseRevokedException extends LicenseException
{
    public function __construct(string $message = 'License has been revoked.')
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return 'Your application license has been suspended. Please contact Softtrill support.';
    }
}
