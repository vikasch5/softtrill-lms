<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Str;

class PrivacyService
{
    /**
     * Get all privacy settings with defaults.
     */
    public function getAll(): array
    {
        $settings = Setting::getBulk('privacy.');

        return [
            'mobile' => [
                'visibility' => $settings['privacy.mobile.visibility'] ?? 'full',
                'mask_type' => $settings['privacy.mobile.mask_type'] ?? 'mask_last',
                'visible_digits' => (int) ($settings['privacy.mobile.visible_digits'] ?? 4),
                'mask_character' => $settings['privacy.mobile.mask_character'] ?? 'X',
            ],
            'email' => [
                'visibility' => $settings['privacy.email.visibility'] ?? 'full',
            ],
            'actions' => [
                'copy_mobile' => (bool) ($settings['privacy.actions.copy_mobile'] ?? 1),
                'copy_email' => (bool) ($settings['privacy.actions.copy_email'] ?? 1),
                'export' => (bool) ($settings['privacy.actions.export'] ?? 0),
                'download' => (bool) ($settings['privacy.actions.download'] ?? 0),
                'print' => (bool) ($settings['privacy.actions.print'] ?? 0),
                'api_access' => (bool) ($settings['privacy.actions.api_access'] ?? 0),
            ],
            'roles' => $this->getRolesSettings($settings),
            'fields' => $this->getFieldsSettings($settings),
        ];
    }

    /**
     * Extract role specific settings.
     */
    private function getRolesSettings(array $settings): array
    {
        $rolesSettings = [];
        foreach ($settings as $key => $value) {
            if (Str::startsWith($key, 'privacy.roles.')) {
                $parts = explode('.', $key);
                if (count($parts) >= 4) {
                    $roleName = $parts[2];
                    $permission = $parts[3];
                    $rolesSettings[$roleName][$permission] = (bool) $value;
                }
            }
        }
        return $rolesSettings;
    }

    /**
     * Extract field specific settings.
     */
    private function getFieldsSettings(array $settings): array
    {
        $fieldsSettings = [];
        foreach ($settings as $key => $value) {
            if (Str::startsWith($key, 'privacy.fields.')) {
                $parts = explode('.', $key);
                if (count($parts) >= 4) {
                    $fieldSlug = $parts[2];
                    $property = $parts[3]; // 'visible' or 'masked'
                    $fieldsSettings[$fieldSlug][$property] = (bool) $value;
                }
            }
        }
        return $fieldsSettings;
    }

    /**
     * Mask a mobile number based on settings.
     */
    public function maskMobile(?string $number, array $mobileSettings): ?string
    {
        if (empty($number)) {
            return $number;
        }

        if (($mobileSettings['visibility'] ?? 'full') === 'full') {
            return $number;
        }

        $maskType = $mobileSettings['mask_type'] ?? 'mask';
        $visibleDigits = (int) ($mobileSettings['visible_digits'] ?? 4);
        $maskChar = $mobileSettings['mask_character'] ?? 'X';
        
        $length = strlen($number);
        
        if ($maskType === 'mask') {
             // 9876543210 -> 98765XXXXX (assuming 5 visible digits, mask remaining)
             $visible = min($visibleDigits, $length);
             $maskedCount = $length - $visible;
             return substr($number, 0, $visible) . str_repeat($maskChar, $maskedCount);
        } elseif ($maskType === 'mask_last') {
             // 9876543210 -> XXXXX43210
             $visible = min($visibleDigits, $length);
             $maskedCount = $length - $visible;
             return str_repeat($maskChar, $maskedCount) . substr($number, -$visible);
        } elseif ($maskType === 'mask_first') {
             // 9876543210 -> 98765XXXXX
             $visible = min($visibleDigits, $length);
             $maskedCount = $length - $visible;
             return substr($number, 0, $visible) . str_repeat($maskChar, $maskedCount);
        }

        // Default fallback (full mask except for basic formatting if any, or just plain mask)
        return str_repeat($maskChar, $length);
    }

    /**
     * Mask an email address based on settings.
     */
    public function maskEmail(?string $email, array $emailSettings): ?string
    {
        if (empty($email)) {
            return $email;
        }

        if (($emailSettings['visibility'] ?? 'full') === 'full') {
            return $email;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email; // Invalid email format
        }

        $localPart = $parts[0];
        $domainPart = $parts[1];

        $visibleLength = min(3, max(1, floor(strlen($localPart) / 2)));
        $maskedLocal = substr($localPart, 0, $visibleLength) . str_repeat('*', strlen($localPart) - $visibleLength);

        return $maskedLocal . '@' . $domainPart;
    }

    /**
     * Check if a role has permission for a specific action per privacy settings.
     */
    public function canRole(string $role, string $action): bool
    {
        // E.g., action = 'unmasked_mobile'
        $key = "privacy.roles.{$role}.{$action}";
        $value = Setting::get($key);
        
        if ($value !== null) {
            return (bool) $value;
        }
        
        if ($role === 'Admin') {
            return true; // Super Admin / Admin always have full access by default
        }
        
        // Defaults if not set
        return false;
    }
}
