<?php

namespace App\Exceptions\License;

class UserLimitExceededException extends LicenseException
{
    public function __construct(string $message = 'User limit exceeded for this license.')
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return 'User limit reached for your license. Please upgrade your plan at softtrill.com.';
    }
}
