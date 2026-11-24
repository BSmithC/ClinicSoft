<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\MedicalSessionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SpecialtyController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'dashboard'])
    ->middleware(['auth', 'role:admin|doctor'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin|doctor')->group(function () {
        // Appointment-specific actions must be declared before the resource routes.
        Route::post('/appointments/{appointment}/status', [AppointmentController::class, 'changeStatus'])
            ->name('appointments.status');
        Route::get('/appointments/{appointment}/history', [AppointmentController::class, 'history'])
            ->name('appointments.history');
        Route::get('/patients/{patient}/history', [PatientController::class, 'history'])
            ->name('patients.history');
        Route::post('/sessions/{session}/cancel', [MedicalSessionController::class, 'cancel'])
            ->name('sessions.cancel');
        Route::get('/doctors/{doctor}/agenda', [DoctorController::class, 'agenda'])
            ->name('doctors.agenda');

        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
        Route::get('/appointments/welcome', [AppointmentController::class, 'welcome'])->name('appointments.welcome');
        Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');

        Route::post('/blocked-dates', [ScheduleController::class, 'store'])->name('blockedDates.store');
        Route::delete('/blocked-dates/{blockedDate}', [ScheduleController::class, 'destroy'])
            ->name('blockedDates.destroy');
        Route::post('/sessions/{session}/breaks', [ScheduleController::class, 'storeBreak'])
            ->name('sessionBreaks.store');
        Route::delete('/breaks/{sessionBreak}', [ScheduleController::class, 'destroyBreak'])
            ->name('sessionBreaks.destroy');

        Route::resource('/patients', PatientController::class);
        Route::resource('/drugs', DrugController::class);
        Route::resource('/bills', BillController::class);
        Route::resource('/appointments', AppointmentController::class);
        Route::resource('/medicalhistories', MedicalHistoryController::class);
        Route::resource('/doctors', DoctorController::class);
        Route::resource('/specialties', SpecialtyController::class);
        Route::resource('/sessions', MedicalSessionController::class)->parameters(['sessions' => 'session']);
    });
});

require __DIR__.'/auth.php';
