<?php

namespace App\Exceptions\License;

class FeatureNotLicensedException extends LicenseException
{
    public function __construct(private readonly string $feature, string $message = '')
    {
        parent::__construct($message ?: "Feature '{$feature}' is not available in your license.");
    }

    public function getFeature(): string
    {
        return $this->feature;
    }

    public function userMessage(): string
    {
        return 'This feature is not available in your current license plan. Please contact Softtrill support.';
    }
}
