<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// ---- Auth ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');   // Step 1
    Route::post('/register', [RegisterController::class, 'store']);
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---- Student account ----
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');           // Step 5
    Route::get('/personal-information', [Student\ProfileController::class, 'edit'])->name('profile');
    Route::put('/personal-information', [Student\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/status', [Student\PageController::class, 'status'])->name('status');                     // Steps 6 & 8
    Route::get('/check-list', [Student\PageController::class, 'checklist'])->name('checklist');
    Route::get('/announcements', [Student\PageController::class, 'announcements'])->name('announcements');
    Route::get('/about-cics', [Student\PageController::class, 'about'])->name('about');
    Route::post('/notifications/{id}/read', [Student\PageController::class, 'readNotification'])->name('notifications.read');

    Route::get('/requirements', [Student\RequirementController::class, 'create'])->name('requirements.create'); // Step 2
    Route::post('/requirements', [Student\RequirementController::class, 'store'])->name('requirements.store');
    Route::get('/documents/{document}', [Student\RequirementController::class, 'show'])->name('documents.show');

    Route::get('/appointments/{type}', [Student\AppointmentController::class, 'create'])->name('appointments.create');  // Steps 3 & 7
    Route::post('/appointments/{type}', [Student\AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointment-slip/{appointment}', [Student\AppointmentController::class, 'slip'])->name('appointments.slip');
});

// ---- Admin ----
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');
    Route::patch('/applications/{application}/exam-result', [Admin\ApplicationController::class, 'examResult'])->name('applications.exam-result');
    Route::patch('/applications/{application}/interview-result', [Admin\ApplicationController::class, 'interviewResult'])->name('applications.interview-result');
    Route::post('/results/release', [Admin\ApplicationController::class, 'releaseResults'])->name('results.release');
    Route::post('/batches/generate', [Admin\ApplicationController::class, 'generateBatches'])->name('batches.generate');
    Route::get('/documents/{document}', [Student\RequirementController::class, 'show'])->name('documents.show');

    Route::get('/announcements', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});
