<?php

use App\Http\Controllers\Api\Mobile\V1\AppConfigController;
use App\Http\Controllers\Api\Mobile\V1\AssignmentController;
use App\Http\Controllers\Api\Mobile\V1\AuthController;
use App\Http\Controllers\Api\Mobile\V1\BeritaAcaraController;
use App\Http\Controllers\Api\Mobile\V1\ClientController;
use App\Http\Controllers\Api\Mobile\V1\ConsultationReportController;
use App\Http\Controllers\Api\Mobile\V1\DashboardController;
use App\Http\Controllers\Api\Mobile\V1\FeedbackController;
use App\Http\Controllers\Api\Mobile\V1\MobileFileController;
use App\Http\Controllers\Api\Mobile\V1\MobileNotificationController;
use App\Http\Controllers\Api\Mobile\V1\ProfileController;
use App\Http\Controllers\Api\Mobile\V1\ScheduleController;
use App\Http\Controllers\Api\Mobile\V1\StaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->middleware('request.id')->group(function (): void {
    Route::get('app-config', AppConfigController::class);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'mobile.active'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

        Route::get('me', [ProfileController::class, 'show']);
        Route::get('me/capabilities', [ProfileController::class, 'capabilities']);
        Route::put('me/device', [ProfileController::class, 'upsertDevice']);
        Route::delete('me/device', [ProfileController::class, 'disableDevice']);

        Route::get('dashboard', DashboardController::class);
        Route::get('staff', StaffController::class);

        Route::get('clients', [ClientController::class, 'index']);
        Route::get('clients/{client}', [ClientController::class, 'show']);
        Route::patch('clients/{client}', [ClientController::class, 'update']);

        Route::post('clients/{client}/schedules', [ScheduleController::class, 'store']);
        Route::patch('clients/{client}/schedules/{schedule}', [ScheduleController::class, 'update']);

        Route::post('clients/{client}/assignments', [AssignmentController::class, 'store']);
        Route::patch('clients/{client}/assignments/{assignment}', [AssignmentController::class, 'update']);

        Route::get('clients/{client}/consultation-reports', [ConsultationReportController::class, 'index']);
        Route::post('clients/{client}/consultation-reports', [ConsultationReportController::class, 'store']);
        Route::patch('clients/{client}/consultation-reports/{report}', [ConsultationReportController::class, 'update']);

        Route::get('clients/{client}/berita-acara', [BeritaAcaraController::class, 'show']);
        Route::post('clients/{client}/berita-acara', [BeritaAcaraController::class, 'store']);
        Route::patch('clients/{client}/berita-acara/{beritaAcara}', [BeritaAcaraController::class, 'update']);
        Route::get('clients/{client}/berita-acara/{beritaAcara}/attendance-link', [BeritaAcaraController::class, 'attendanceLink']);
        Route::get('clients/{client}/berita-acara/{beritaAcara}/signing-links', [BeritaAcaraController::class, 'signingLinks']);

        Route::get('clients/{client}/files/{path}', [MobileFileController::class, 'file'])->where('path', '.*');
        Route::get('clients/{client}/ticket', [MobileFileController::class, 'ticket']);
        Route::get('clients/{client}/report-pdf', [MobileFileController::class, 'report']);
        Route::get('clients/{client}/berita-acara-pdf', [MobileFileController::class, 'beritaAcara']);

        Route::get('satisfaction-surveys', [FeedbackController::class, 'satisfactionIndex']);
        Route::get('satisfaction-surveys/{survey}', [FeedbackController::class, 'satisfactionShow']);
        Route::get('public-feedback', [FeedbackController::class, 'publicIndex']);
        Route::get('public-feedback/{feedback}', [FeedbackController::class, 'publicShow']);

        Route::get('notifications', [MobileNotificationController::class, 'index']);
        Route::get('notifications/unread-count', [MobileNotificationController::class, 'unreadCount']);
        Route::get('notifications/{notification}', [MobileNotificationController::class, 'show']);
        Route::patch('notifications/{notification}/read', [MobileNotificationController::class, 'read']);
        Route::post('notifications/read-all', [MobileNotificationController::class, 'readAll']);
    });
});
