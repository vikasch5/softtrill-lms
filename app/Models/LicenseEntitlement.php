<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseEntitlement extends Model
{
    protected $table = 'license_entitlements';

    protected $fillable = [
        'installation_id',
        'signed_payload',
        'payload_hash',
        'license_id',
        'payload_issued_at',
        'payload_expires_at',
        'cached_until',
        'cached_status',
    ];

    protected $casts = [
        'payload_issued_at'  => 'datetime',
        'payload_expires_at' => 'datetime',
        'cached_until'       => 'datetime',
    ];

    /**
     * The signed_payload should not be mass-serialised to JSON responses.
     */
    protected $hidden = ['signed_payload'];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(LicenseInstallation::class, 'installation_id', 'installation_id');
    }

    /**
     * Whether the cached validation interval has expired (time to re-validate).
     */
    public function needsRevalidation(): bool
    {
        return $this->cached_until === null || $this->cached_until->isPast();
    }

    /**
     * Whether the payload itself has expired (license expired, not just cache).
     */
    public function isPayloadExpired(): bool
    {
        return $this->payload_expires_at !== null && $this->payload_expires_at->isPast();
    }
}
