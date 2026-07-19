<?php

use App\Http\Controllers\Lms\AuthController;
use App\Http\Controllers\Lms\DashboardController;
use App\Http\Controllers\Lms\LeadController;
use App\Http\Controllers\Lms\UserController;
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
    Route::get('/field-list', [LeadController::class, 'fieldList'])->name('lms.lead-fields.list');
    Route::get('/field-add/{id?}', [LeadController::class, 'fieldAddIndex'])->name('lms.lead-fields.add');
    Route::post('/field-save', [LeadController::class, 'fieldStoreOrUpdate'])->name('lms.lead-fields.store');
    Route::delete('/field-delete', [LeadController::class, 'delete'])->name('lms.lead-fields.delete');
    Route::get('/lead-import', [LeadController::class, 'leadImport'])->name('lms.lead.import');
    Route::post('/lead-import', [LeadController::class, 'import'])->name('lms.leads.import.save');
    Route::get('/lead-sample/{id}', [LeadController::class, 'downloadSample'])->name('lms.leads.sample');
    Route::get('/lead-add/{id?}', [LeadController::class, 'leadAdd'])->name('lms.leads.add');
    Route::post('/lead-save', [LeadController::class, 'storeOrUpdate'])->name('lms.leads.store');
    Route::post('/lead-delete', [LeadController::class, 'leadDelete'])->name('lms.leads.delete');
    Route::post('/lead-assign', [LeadController::class, 'assignLeads'])->name('lms.leads.assign');
    Route::get('/api/supervisors-by-manager', [LeadController::class, 'getSupervisorsByManager'])->name('lms.api.supervisors-by-manager');
    Route::get('/api/users-by-supervisor', [LeadController::class, 'getUsersBySupervisor'])->name('lms.api.users-by-supervisor');
    Route::get('/leads', [LeadController::class, 'leadsList'])->name('lms.leads');
    Route::get('/lead/{id}', [LeadController::class, 'leadsEdit'])->name('lms.lead.edit');
    Route::get('/lead-view/{id}', [LeadController::class, 'leadsView'])->name('lms.lead.view');
    Route::post('/lead-update', [LeadController::class, 'updateLead'])->name('lms.leads.update');
    Route::post('/lead-quick-save', [LeadController::class, 'quickUpdate'])->name('lms.leads.quick-update');
    Route::post('/lead-note-save', [LeadController::class, 'updateLead'])->name('lms.leads.note.store');
    Route::get('/feedback-list', [LeadController::class, 'feedbackList'])->name('lms.feedbacks.list');
    Route::get('/feedback-add/{id?}', [LeadController::class, 'feedbackAdd'])->name('lms.feedbacks.add');
    Route::post('/feedback-save', [LeadController::class, 'feedbackStoreOrUpdate'])->name('lms.feedbacks.store');
    Route::post('/feedback-delete', [LeadController::class, 'feedbackDelete'])->name('lms.feedbacks.delete');
    Route::get('/feedbacks/sub-feedbacks/{feedbackId}', [LeadController::class, 'subFeedbacks'])->name('lms.feedbacks.sub-feedbacks');

    Route::get('/dashboard-widget-list', [DashboardController::class, 'widgetsList'])->name('lms.dashboard.widgets.list');
    Route::get('/dashboard-widget', [DashboardController::class, 'dashboardWidget'])->name('lms.dashboard.widgets');
    Route::get('/dashboard-widget-edit/{id}', [DashboardController::class, 'editWidget'])->name('lms.dashboard.widgets.edit');
    Route::post('/dashboard-widget-store', [DashboardController::class, 'dashboardWidgetStore'])->name('lms.dashboard.widgets.store');
    Route::get('/dashboard/widgets/fields/{list}', [DashboardController::class, 'getFields'])
    ->name('lms.dashboard.widgets.fields');
    Route::get('/dashboard/widget-data/{id}', [DashboardController::class, 'widgetData'])
    ->name('lms.dashboard.widget.data');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});
