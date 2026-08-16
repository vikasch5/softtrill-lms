<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseSecurityLog extends Model
{
    protected $table = 'license_security_log';

    // Immutable — no updated_at
    public $timestamps    = false;
    const CREATED_AT      = 'created_at';

    protected $fillable = [
        'installation_id',
        'event_type',
        'severity',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    // -----------------------------------------------------------------------
    // Event type constants — use these everywhere; never use raw strings
    // -----------------------------------------------------------------------
    const EVENT_ACTIVATION_SUCCESS     = 'activation.success';
    const EVENT_ACTIVATION_FAILURE     = 'activation.failure';
    const EVENT_DEACTIVATION           = 'deactivation';
    const EVENT_VALIDATION_SUCCESS     = 'validation.success';
    const EVENT_VALIDATION_FAILURE     = 'validation.failure';
    const EVENT_SIGNATURE_FAILURE      = 'signature.failure';
    const EVENT_PAYLOAD_TAMPERED       = 'payload.tampered';
    const EVENT_INSTALLATION_MISMATCH  = 'installation.mismatch';
    const EVENT_DOMAIN_MISMATCH        = 'domain.mismatch';
    const EVENT_LICENSE_EXPIRED        = 'license.expired';
    const EVENT_LICENSE_REVOKED        = 'license.revoked';
    const EVENT_USER_LIMIT_EXCEEDED    = 'user_limit.exceeded';
    const EVENT_GRACE_PERIOD_ENTERED   = 'grace_period.entered';
    const EVENT_GRACE_PERIOD_EXPIRED   = 'grace_period.expired';
    const EVENT_TAMPER_DETECTED        = 'tamper.detected';
    const EVENT_REPLAY_ATTEMPT         = 'replay.attempt';
    const EVENT_API_AUTH_FAILURE       = 'api.auth_failure';
    const EVENT_SERVER_UNREACHABLE     = 'server.unreachable';
    const EVENT_FEATURE_DENIED         = 'feature.denied';

    // -----------------------------------------------------------------------
    // Severity constants
    // -----------------------------------------------------------------------
    const SEVERITY_INFO     = 'info';
    const SEVERITY_WARNING  = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Convenience static factory.
     */
    public static function record(
        string $eventType,
        string $severity = self::SEVERITY_INFO,
        array $details = [],
        ?string $installationId = null
    ): self {
        return self::create([
            'installation_id' => $installationId,
            'event_type'      => $eventType,
            'severity'        => $severity,
            'details'         => $details,
            'ip_address'      => request()->ip(),
            'user_agent'      => substr((string) request()->userAgent(), 0, 512),
        ]);
    }
}
