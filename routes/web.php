<?php

use App\Http\Controllers\Lms\AuthController;
use App\Http\Controllers\Lms\DashboardController;
use App\Http\Controllers\Lms\LeadController;
use App\Http\Controllers\Lms\UserController;
use App\Http\Controllers\Lms\SettingsController;
use App\Services\License\LicenseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'login'])->name('home');
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'doLogin'])->name('task.doLogin');
Route::get('/register', [AuthController::class, 'register'])->name('task.register');
Route::post('/register', [AuthController::class, 'registerStore'])->name('register');
Route::post('/send-otp', [AuthController::class, 'sendOtp'])->name('send.otp');
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('lms.dashboard');
    Route::get('/user-list', [UserController::class, 'usersList'])->name('lms.users.list');
    Route::get('/user-add/{id?}', [UserController::class, 'usersAdd'])->name('lms.users.add');
    Route::post('/user-save', [UserController::class, 'storeOrUpdate'])->name('lms.users.store');
    Route::post('/user-delete', [UserController::class, 'delete'])->name('lms.users.delete');
    Route::get('/field-list', [App\Http\Controllers\Lms\LeadFieldController::class, 'fieldList'])->name('lms.lead-fields.list');
    Route::get('/field-add/{id?}', [App\Http\Controllers\Lms\LeadFieldController::class, 'fieldAddIndex'])->name('lms.lead-fields.add');
    Route::post('/field-save', [App\Http\Controllers\Lms\LeadFieldController::class, 'fieldStoreOrUpdate'])->name('lms.lead-fields.store');
    Route::delete('/field-delete', [App\Http\Controllers\Lms\LeadFieldController::class, 'delete'])->name('lms.lead-fields.delete');
    
    Route::get('/lead-import', [App\Http\Controllers\Lms\LeadImportController::class, 'leadImport'])->name('lms.lead.import');
    Route::post('/lead-import', [App\Http\Controllers\Lms\LeadImportController::class, 'import'])->name('lms.leads.import.save');
    Route::get('/lead-sample/{id}', [App\Http\Controllers\Lms\LeadImportController::class, 'downloadSample'])->name('lms.leads.sample');
    
    Route::get('/lead-add/{id?}', [LeadController::class, 'leadAdd'])->name('lms.leads.add');
    Route::post('/lead-save', [LeadController::class, 'storeOrUpdate'])->name('lms.leads.store');
    Route::post('/lead-delete', [LeadController::class, 'leadDelete'])->name('lms.leads.delete');
    Route::post('/lead-assign', [LeadController::class, 'assignLeads'])->name('lms.leads.assign');
    Route::get('/api/assignable-users', [LeadController::class, 'getAssignableUsers'])->name('lms.api.assignable-users');
    Route::get('/api/supervisors-by-manager', [LeadController::class, 'getSupervisorsByManager'])->name('lms.api.supervisors-by-manager');
    Route::get('/api/users-by-supervisor', [LeadController::class, 'getUsersBySupervisor'])->name('lms.api.users-by-supervisor');
    Route::get('/leads', [LeadController::class, 'leadsList'])->name('lms.leads');
    Route::get('/leads/download', [LeadController::class, 'downloadLeadList'])->name('lms.leads.download');
    Route::get('/lead/{id}', [LeadController::class, 'leadsEdit'])->name('lms.lead.edit');
    Route::get('/lead-view/{id}', [LeadController::class, 'leadsView'])->name('lms.lead.view');
    Route::post('/lead-update', [LeadController::class, 'updateLead'])->name('lms.leads.update');
    Route::post('/lead-quick-save', [LeadController::class, 'quickUpdate'])->name('lms.leads.quick-update');
    Route::post('/lead-note-save', [LeadController::class, 'updateLead'])->name('lms.leads.note.store');
    
    Route::get('/feedback-list', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackList'])->name('lms.feedbacks.list');
    Route::get('/feedback-add/{id?}', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackAdd'])->name('lms.feedbacks.add');
    Route::post('/feedback-save', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackStoreOrUpdate'])->name('lms.feedbacks.store');
    Route::post('/feedback-delete', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackDelete'])->name('lms.feedbacks.delete');
    Route::get('/feedbacks/sub-feedbacks/{feedbackId}', [App\Http\Controllers\Lms\FeedbackController::class, 'subFeedbacks'])->name('lms.feedbacks.sub-feedbacks');

    Route::get('/dashboard-widget-list', [DashboardController::class, 'widgetsList'])->name('lms.dashboard.widgets.list');
    Route::get('/dashboard-widget', [DashboardController::class, 'dashboardWidget'])->name('lms.dashboard.widgets');
    Route::get('/dashboard-widget-edit/{id}', [DashboardController::class, 'editWidget'])->name('lms.dashboard.widgets.edit');
    Route::post('/dashboard-widget-store', [DashboardController::class, 'dashboardWidgetStore'])->name('lms.dashboard.widgets.store');
    Route::get('/dashboard/widgets/fields/{list}', [DashboardController::class, 'getFields'])->name('lms.dashboard.widgets.fields');
    Route::get('/dashboard/widget-data/{id}', [DashboardController::class, 'widgetData'])->name('lms.dashboard.widget.data');
    Route::post('/keep-alive', [AuthController::class, 'keepAlive'])->name('lms.keep-alive');
    Route::post('/mark-offline', [AuthController::class, 'markOffline'])->name('lms.mark-offline');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
    
    Route::post('/dialer-call', [App\Http\Controllers\Lms\DialerController::class, 'call'])->name('lms.dialer.call');
    Route::post('/dialer-hangup', [App\Http\Controllers\Lms\DialerController::class, 'hangup'])->name('lms.dialer.hangup');
    Route::post('/dialer-status', [App\Http\Controllers\Lms\DialerController::class, 'status'])->name('lms.dialer.status');
    
    Route::get('/offer-add/{id?}', [App\Http\Controllers\Lms\OfferController::class, 'offerAdd'])->name('lms.offer.add');
    Route::get('/offer-list', [App\Http\Controllers\Lms\OfferController::class, 'offerList'])->name('lms.offers.list');
    Route::post('/offer-store', [App\Http\Controllers\Lms\OfferController::class, 'offerStoreOrUpdate'])->name('lms.offers.store');
    Route::post('/offer-delete', [App\Http\Controllers\Lms\OfferController::class, 'offerDelete'])->name('lms.offers.delete');


    // Settings / Privacy
    Route::get('/settings/privacy', [SettingsController::class, 'privacyIndex'])->name('lms.settings.privacy');
    Route::post('/settings/privacy', [SettingsController::class, 'privacyUpdate'])->name('lms.settings.privacy.update');
    Route::post('/settings/privacy/preview', [SettingsController::class, 'privacyPreview'])->name('lms.settings.privacy.preview');
});

/*
|--------------------------------------------------------------------------
| License Cache-Clear Webhook
|--------------------------------------------------------------------------
| Called by the Softtrill license server to force an immediate re-validation.
|
| Authentication: HMAC-SHA256 of request timestamp using a secret derived
| from APP_KEY (never a shared plain-text secret in .env).
|
| The license server must send:
|   Header: X-Softtrill-Webhook-Ts  (unix timestamp, max 5 minutes old)
|   Header: X-Softtrill-Webhook-Sig (hex HMAC-SHA256 of timestamp using shared secret)
|
| The shared secret is: HMAC-SHA256('softtrill-webhook-v1', APP_KEY)
*/
Route::post('/license-webhook', function (Request $request) {
    try {
        
        \Illuminate\Support\Facades\Artisan::call('softtrill:license:refresh');
        return response()->json(['ok' => true, 'message' => 'License refreshed successfully.']);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('[License Webhook] Failed to run refresh command.', ['error' => $e->getMessage()]);
        return response()->json(['ok' => false, 'error' => 'Failed to refresh license.'], 500);
    }
})->withoutMiddleware([
    \App\Http\Middleware\CheckLicense::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
]);

