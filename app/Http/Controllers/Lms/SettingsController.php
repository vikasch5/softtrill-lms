<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\LeadField;
use App\Models\Setting;
use App\Services\PrivacyService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    protected $privacyService;

    public function __construct(PrivacyService $privacyService)
    {
        $this->privacyService = $privacyService;
    }

    public function privacyIndex()
    {
        if (!auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized access to privacy settings.');
        }

        $settings = $this->privacyService->getAll();
        
        $roles = Role::all();
        
        // Fetch all unique fields across all lists
        $leadFields = LeadField::select('name', 'slug')->distinct()->get();

        return view('lms.pages.privacy-security', compact('settings', 'roles', 'leadFields'));
    }

    public function privacyUpdate(Request $request)
    {
        if (!auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized access to privacy settings.');
        }

        // Validate basic expected structure (though many fields are dynamic toggles)
        $request->validate([
            'privacy_mobile_visibility' => 'required|in:full,mask,mask_last,mask_first',
            'privacy_mobile_visible_digits' => 'required_unless:privacy_mobile_visibility,full|integer|min:0',
            'privacy_mobile_mask_character' => 'required_unless:privacy_mobile_visibility,full|string|max:1',
            'privacy_email_visibility' => 'required|in:full,mask',
        ]);

        // Mobile Settings
        Setting::set('privacy.mobile.visibility', $request->privacy_mobile_visibility);
        Setting::set('privacy.mobile.mask_type', $request->privacy_mobile_mask_type ?? $request->privacy_mobile_visibility);
        Setting::set('privacy.mobile.visible_digits', $request->privacy_mobile_visible_digits ?? 5);
        Setting::set('privacy.mobile.mask_character', $request->privacy_mobile_mask_character ?? 'X');

        // Email Settings
        Setting::set('privacy.email.visibility', $request->privacy_email_visibility);

        // Fixed Fields
        $fixedFields = ['mobile', 'email', 'alternate_number', 'address'];
        foreach ($fixedFields as $field) {
            Setting::set("privacy.fields.{$field}.visible", $request->input("privacy_fields_{$field}_visible", 0));
            Setting::set("privacy.fields.{$field}.masked", $request->input("privacy_fields_{$field}_masked", 0));
        }

        // Dynamic Fields
        if ($request->has('privacy_fields')) {
            foreach ($request->privacy_fields as $slug => $options) {
                Setting::set("privacy.fields.{$slug}.visible", isset($options['visible']) ? 1 : 0);
                Setting::set("privacy.fields.{$slug}.masked", isset($options['masked']) ? 1 : 0);
            }
        }

        // Actions
        $actions = ['copy_mobile', 'copy_email', 'export', 'download', 'print', 'api_access'];
        foreach ($actions as $action) {
            Setting::set("privacy.actions.{$action}", $request->input("privacy_actions_{$action}", 0));
        }

        // Roles
        $roles = Role::all();
        foreach ($roles as $role) {
            $roleName = $role->name;
            Setting::set("privacy.roles.{$roleName}.view_data", $request->input("privacy_roles_{$roleName}_view_data", 0));
            Setting::set("privacy.roles.{$roleName}.unmasked_mobile", $request->input("privacy_roles_{$roleName}_unmasked_mobile", 0));
            Setting::set("privacy.roles.{$roleName}.unmasked_email", $request->input("privacy_roles_{$roleName}_unmasked_email", 0));
            Setting::set("privacy.roles.{$roleName}.export", $request->input("privacy_roles_{$roleName}_export", 0));
        }

        return response()->json([
            'success' => true,
            'message' => 'Privacy settings saved successfully.',
            'notify_type' => 'toast'
        ]);
    }

    public function privacyPreview(Request $request)
    {
        $mobile = '9876543210';
        $email = 'john.doe@gmail.com';

        $mobileSettings = [
            'visibility' => $request->visibility ?? 'mask_last',
            'mask_type' => $request->visibility ?? 'mask_last',
            'visible_digits' => (int) ($request->visible_digits ?? 5),
            'mask_character' => $request->mask_character ?? 'X',
        ];

        $emailSettings = [
            'visibility' => $request->email_visibility ?? 'mask',
        ];

        $maskedMobile = $this->privacyService->maskMobile($mobile, $mobileSettings);
        $maskedEmail = $this->privacyService->maskEmail($email, $emailSettings);

        return response()->json([
            'original_mobile' => $mobile,
            'masked_mobile' => $maskedMobile,
            'original_email' => $email,
            'masked_email' => $maskedEmail,
        ]);
    }
}
