<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LicenseInstallation extends Model
{
    protected $table = 'license_installations';

    protected $fillable = [
        'installation_id',
        'installation_hmac',
        'api_credential',
        'api_credential_hash',
        'domain',
        'status',
        'activated_at',
        'last_validated_at',
        'grace_expires_at',
        'last_server_time',
        'last_successful_validation_at',
    ];

    protected $casts = [
        'activated_at'     => 'datetime',
        'last_validated_at' => 'datetime',
        'grace_expires_at' => 'datetime',
    ];

    /**
     * Never expose the api_credential in serialisation.
     */
    protected $hidden = [
        'api_credential',
        'api_credential_hash',
        'installation_hmac',
    ];

    public function entitlements(): HasMany
    {
        return $this->hasMany(LicenseEntitlement::class, 'installation_id', 'installation_id');
    }

    public function latestEntitlement(): HasOne
    {
        return $this->hasOne(LicenseEntitlement::class, 'installation_id', 'installation_id')
            ->latest();
    }

    public function securityLogs(): HasMany
    {
        return $this->hasMany(LicenseSecurityLog::class, 'installation_id', 'installation_id');
    }
}
