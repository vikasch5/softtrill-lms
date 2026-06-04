<?php

use App\Http\Controllers\Lms\AuthController;
use App\Http\Controllers\Lms\DashboardController;
use App\Http\Controllers\Lms\LeadController;
use App\Http\Controllers\Lms\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'login'])->name('login');
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

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});
