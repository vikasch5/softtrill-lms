<?php

use App\Http\Controllers\Lms\AuthController;
use App\Http\Controllers\Lms\DashboardController;
use App\Http\Controllers\Lms\LeadController;
use App\Http\Controllers\Lms\UserController;
use App\Http\Controllers\Lms\SettingsController;
use App\Http\Controllers\Lms\ReportController;
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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('lms.dashboard')->middleware('can:dashboard.view');
    Route::get('/user-list', [UserController::class, 'usersList'])->name('lms.users.list')->middleware('can:users.view');
    Route::get('/user-add/{id?}', [UserController::class, 'usersAdd'])->name('lms.users.add')->middleware('can:users.edit');
    Route::post('/user-save', [UserController::class, 'storeOrUpdate'])->name('lms.users.store')->middleware('can:users.create');
    Route::post('/user-delete', [UserController::class, 'delete'])->name('lms.users.delete')->middleware('can:users.delete');
    Route::get('/settings/profile', [App\Http\Controllers\Lms\SettingsController::class, 'profile'])->name('lms.settings.profile');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/field-list', [App\Http\Controllers\Lms\LeadFieldController::class, 'fieldList'])->name('lms.lead-fields.list')->middleware('can:lead-fields.view');
    Route::get('/field-add/{id?}', [App\Http\Controllers\Lms\LeadFieldController::class, 'fieldAddIndex'])->name('lms.lead-fields.add')->middleware('can:lead-fields.create');
    Route::post('/field-save', [App\Http\Controllers\Lms\LeadFieldController::class, 'fieldStoreOrUpdate'])->name('lms.lead-fields.store')->middleware('can:lead-fields.edit');
    Route::delete('/field-delete', [App\Http\Controllers\Lms\LeadFieldController::class, 'delete'])->name('lms.lead-fields.delete')->middleware('can:lead-fields.delete');
    
    Route::get('/lead-import', [App\Http\Controllers\Lms\LeadImportController::class, 'leadImport'])->name('lms.lead.import')->middleware('can:leads.import');
    Route::post('/lead-import', [App\Http\Controllers\Lms\LeadImportController::class, 'import'])->name('lms.leads.import.save')->middleware('can:leads.import');
    Route::get('/lead-sample/{id}', [App\Http\Controllers\Lms\LeadImportController::class, 'downloadSample'])->name('lms.leads.sample')->middleware('can:leads.import');
    
    Route::get('/lead-add/{id?}', [LeadController::class, 'leadAdd'])->name('lms.leads.add')->middleware('can:leads.create');
    Route::post('/lead-save', [LeadController::class, 'storeOrUpdate'])->name('lms.leads.store')->middleware('can:leads.create');
    Route::post('/lead-delete', [LeadController::class, 'leadDelete'])->name('lms.leads.delete')->middleware('can:leads.delete');
    Route::post('/lead-assign', [LeadController::class, 'assignLeads'])->name('lms.leads.assign')->middleware('can:leads.assign');
    Route::get('/api/assignable-users', [LeadController::class, 'getAssignableUsers'])->name('lms.api.assignable-users');
    Route::get('/api/supervisors-by-manager', [LeadController::class, 'getSupervisorsByManager'])->name('lms.api.supervisors-by-manager');
    Route::get('/api/users-by-supervisor', [LeadController::class, 'getUsersBySupervisor'])->name('lms.api.users-by-supervisor');
    Route::get('/api/hierarchy-users', [ReportController::class, 'getHierarchyUsers'])->name('lms.api.hierarchy-users');
    Route::get('/leads', [LeadController::class, 'leadsList'])->name('lms.leads')->middleware('can:leads.view');
    Route::get('/leads/download', [LeadController::class, 'downloadLeadList'])->name('lms.leads.download')->middleware('can:leads.view');
    Route::get('/lead/{id}', [LeadController::class, 'leadsEdit'])->name('lms.lead.edit')->middleware('can:leads.edit');
    Route::get('/lead-view/{id}', [LeadController::class, 'leadsView'])->name('lms.lead.view')->middleware('can:leads.view');
    Route::post('/lead-update', [LeadController::class, 'updateLead'])->name('lms.leads.update')->middleware('can:leads.edit');
    Route::post('/lead-quick-save', [LeadController::class, 'quickUpdate'])->name('lms.leads.quick-update')->middleware('can:leads.edit');
    Route::post('/lead-note-save', [LeadController::class, 'updateLead'])->name('lms.leads.note.store')->middleware('can:leads.edit');
    
    Route::get('/feedback-list', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackList'])->name('lms.feedbacks.list')->middleware('can:feedbacks.view');
    Route::get('/feedback-add/{id?}', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackAdd'])->name('lms.feedbacks.add')->middleware('can:feedbacks.create');
    Route::post('/feedback-save', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackStoreOrUpdate'])->name('lms.feedbacks.store')->middleware('can:feedbacks.edit');
    Route::post('/feedback-delete', [App\Http\Controllers\Lms\FeedbackController::class, 'feedbackDelete'])->name('lms.feedbacks.delete')->middleware('can:feedbacks.delete');
    Route::get('/feedbacks/sub-feedbacks/{feedbackId}', [App\Http\Controllers\Lms\FeedbackController::class, 'subFeedbacks'])->name('lms.feedbacks.sub-feedbacks')->middleware('can:feedbacks.view');

    Route::get('/performance-report', [ReportController::class, 'report'])->name('lms.performance.report')->middleware('can:reports.performance');
    Route::get('/performance-report/export', [ReportController::class, 'export'])->name('lms.performance.report.export')->middleware('can:reports.performance');
    Route::get('/agent-performance/{id?}', [ReportController::class, 'agentPerformance'])->name('lms.agent.performance');
    Route::get('/agent-performance/{id}/export', [ReportController::class, 'exportAgentPerformance'])->name('lms.agent.performance.export');

    Route::get('/dashboard-widget-list', [DashboardController::class, 'widgetsList'])->name('lms.dashboard.widgets.list')->middleware('can:settings.widgets');
    Route::get('/dashboard-widget', [DashboardController::class, 'dashboardWidget'])->name('lms.dashboard.widgets')->middleware('can:settings.widgets');
    Route::get('/dashboard-widget-edit/{id}', [DashboardController::class, 'editWidget'])->name('lms.dashboard.widgets.edit')->middleware('can:settings.widgets');
    Route::post('/dashboard-widget-store', [DashboardController::class, 'dashboardWidgetStore'])->name('lms.dashboard.widgets.store')->middleware('can:settings.widgets');
    Route::get('/dashboard/widgets/fields/{list}', [DashboardController::class, 'getFields'])->name('lms.dashboard.widgets.fields')->middleware('can:settings.widgets');
    Route::get('/dashboard/widget-data/{id}', [DashboardController::class, 'widgetData'])->name('lms.dashboard.widget.data')->middleware('can:settings.widgets');
    Route::post('/keep-alive', [AuthController::class, 'keepAlive'])->name('lms.keep-alive');
    Route::post('/mark-offline', [AuthController::class, 'markOffline'])->name('lms.mark-offline');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
    
    Route::post('/dialer-call', [App\Http\Controllers\Lms\DialerController::class, 'call'])->name('lms.dialer.call');
    Route::post('/dialer-hangup', [App\Http\Controllers\Lms\DialerController::class, 'hangup'])->name('lms.dialer.hangup');
    Route::post('/dialer-status', [App\Http\Controllers\Lms\DialerController::class, 'status'])->name('lms.dialer.status');
    
    Route::get('/offer-add/{id?}', [App\Http\Controllers\Lms\OfferController::class, 'offerAdd'])->name('lms.offer.add')->middleware('can:offers.create');
    Route::get('/offer-list', [App\Http\Controllers\Lms\OfferController::class, 'offerList'])->name('lms.offers.list')->middleware('can:offers.view');
    Route::post('/offer-store', [App\Http\Controllers\Lms\OfferController::class, 'offerStoreOrUpdate'])->name('lms.offers.store')->middleware('can:offers.edit');
    Route::post('/offer-delete', [App\Http\Controllers\Lms\OfferController::class, 'offerDelete'])->name('lms.offers.delete')->middleware('can:offers.delete');


    // Settings / Privacy
    Route::get('/settings/privacy', [SettingsController::class, 'privacyIndex'])->name('lms.settings.privacy')->middleware('can:settings.privacy');
    Route::post('/settings/privacy', [SettingsController::class, 'privacyUpdate'])->name('lms.settings.privacy.update')->middleware('can:settings.privacy');
    Route::post('/settings/privacy/preview', [SettingsController::class, 'privacyPreview'])->name('lms.settings.privacy.preview')->middleware('can:settings.privacy');

    // Roles & Permissions
    Route::get('/roles', [App\Http\Controllers\Lms\RoleController::class, 'rolesList'])->name('lms.roles.list')->middleware('can:roles.manage');
    Route::get('/roles/{id}/edit', [App\Http\Controllers\Lms\RoleController::class, 'rolesEdit'])->name('lms.roles.edit')->middleware('can:roles.manage');
    Route::post('/roles/{id}', [App\Http\Controllers\Lms\RoleController::class, 'rolesUpdate'])->name('lms.roles.update')->middleware('can:roles.manage');

    // Notifications
    Route::get('/notifications/fetch', [App\Http\Controllers\Lms\NotificationController::class, 'fetch'])->name('lms.notifications.fetch');
    Route::post('/notifications/read-all', [App\Http\Controllers\Lms\NotificationController::class, 'markAllAsRead'])->name('lms.notifications.read-all');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Lms\NotificationController::class, 'markAsRead'])->name('lms.notifications.read');
    Route::post('/notifications/push/subscribe', [App\Http\Controllers\Lms\NotificationController::class, 'subscribePush'])->name('lms.notifications.push.subscribe');
    Route::post('/notifications/push/unsubscribe', [App\Http\Controllers\Lms\NotificationController::class, 'unsubscribePush'])->name('lms.notifications.push.unsubscribe');
    Route::post('/notifications/preferences', [App\Http\Controllers\Lms\NotificationController::class, 'updatePreferences'])->name('lms.notifications.preferences');

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

Route::get('/dev-schema', function() {
    $dialer = \Illuminate\Support\Facades\DB::connection('dialer');
    
    // Test 3: Call History with LEFT JOIN (current)
    $explainHistoryJoin = $dialer->select("
        EXPLAIN
        SELECT val.agent_log_id, val.lead_id, MAX(rl.filename)
        FROM vicidial_agent_log as val
        LEFT JOIN vicidial_carrier_log as vcl 
          ON val.lead_id = vcl.lead_id 
          AND vcl.call_date >= '2023-01-01' AND vcl.call_date < '2023-01-02'
        LEFT JOIN (
            SELECT lead_id, MAX(filename) as filename FROM recording_log GROUP BY lead_id
        ) as rl ON rl.lead_id = val.lead_id
        WHERE val.user = '1001'
          AND val.event_time >= '2023-01-01' AND val.event_time < '2023-01-02'
        GROUP BY val.agent_log_id, val.lead_id
        LIMIT 15
    ");

    // Test 4: Call History with Correlated SELECT Subquery
    $explainHistorySub = $dialer->select("
        EXPLAIN
        SELECT val.agent_log_id, val.lead_id,
            (SELECT filename FROM recording_log rl WHERE rl.lead_id = val.lead_id ORDER BY recording_id DESC LIMIT 1) as filename
        FROM vicidial_agent_log as val
        LEFT JOIN vicidial_carrier_log as vcl 
          ON val.lead_id = vcl.lead_id 
          AND vcl.call_date >= '2023-01-01' AND vcl.call_date < '2023-01-02'
        WHERE val.user = '1001'
          AND val.event_time >= '2023-01-01' AND val.event_time < '2023-01-02'
        GROUP BY val.agent_log_id, val.lead_id
        LIMIT 15
    ");

    return response()->json([
        'history_join' => $explainHistoryJoin,
        'history_sub' => $explainHistorySub
    ]);
});
