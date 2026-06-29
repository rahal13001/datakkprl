<?php

use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\LearningTrackingController;
use App\Livewire\AttendeeSign;
use App\Livewire\BelajarKkprl;
use App\Livewire\BelajarKkprlGroup;
use App\Livewire\CheckStatus;
use App\Livewire\LandingPage;
use App\Livewire\PublicAttendance;
// Assuming we might need this or use closure
use App\Livewire\PublicFeedbackPage;
use App\Livewire\SatisfactionSurveyResults;
use App\Livewire\ServicePerformanceResults;
use App\Models\Client;
use App\Models\Regulation;
use App\Services\ContentDeliveryService;
use App\Services\PrivateFileService;
use App\Services\SignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Admin Panel fallback or specific domain if needed (Laravel Filament usually handles its own routes via panel provider)
// But for our Custom Frontend:

$domain = 'kawanruanglaut.timurbersinar.com';

// Local dev fallback
if (app()->isLocal()) {
    // For local testing, we might want to just map everything or use a specific prefix if domain routing is hard
    // But user asked for domain routing.
    // We can use a pattern that matches the domain or is the default if accessed via IP/localhost for now?
    // Let's stick to the request: domain routing.
    // NOTE: User must set up host file for this to work locally.
}

// In local, we allow access from any domain (localhost, ip, etc)
// In production, we assume strict domain
$routingConfig = app()->environment(['local', 'testing']) ? [] : ['domain' => $domain];

Route::group($routingConfig, function () {
    Route::get('/', LandingPage::class)->name('landing');
    Route::get('/hasil-survei-kepuasan', SatisfactionSurveyResults::class)->name('satisfaction-survey-results');
    Route::get('/hasil-kinerja-layanan', ServicePerformanceResults::class)->name('service-performance-results');
    Route::get('/masukan-publik', PublicFeedbackPage::class)->name('public-feedback');
    Route::get('/cek-status', CheckStatus::class)->name('check-status');
    Route::get('/belajar-kkprl', BelajarKkprl::class)->name('belajar-kkprl');
    Route::get('/belajar-kkprl/materi/{material:slug}', [LearningMaterialController::class, 'show'])->name('belajar-kkprl.material.show');
    Route::get('/belajar-kkprl/materi/{material:slug}/pdf', [LearningMaterialController::class, 'pdf'])->name('belajar-kkprl.material.pdf');
    Route::get('/belajar-kkprl/materi/{material:slug}/download', [LearningMaterialController::class, 'download'])->name('belajar-kkprl.material.download');
    Route::post('/learning/material-access', [LearningTrackingController::class, 'storeAccess'])->middleware('throttle:10,1')->name('learning.access.store');
    Route::get('/learning/material-access/me', [LearningTrackingController::class, 'me'])->middleware('throttle:60,1')->name('learning.access.me');
    Route::post('/learning/session/start', [LearningTrackingController::class, 'startSession'])->middleware('throttle:30,1')->name('learning.session.start');
    Route::post('/learning/session/end', [LearningTrackingController::class, 'endSession'])->middleware('throttle:60,1')->name('learning.session.end');
    Route::post('/learning/activity', [LearningTrackingController::class, 'activity'])->middleware('throttle:120,1')->name('learning.activity.store');
    Route::get('/belajar-kkprl/{group:slug}', BelajarKkprlGroup::class)->name('belajar-kkprl.group');

    // Regulation Preview/Download (Public)
    Route::get('/regulasi/{slug}', function ($slug) {
        $regulation = Regulation::where('slug', $slug)->firstOrFail();

        // Increment download count
        app(ContentDeliveryService::class)->incrementDownloadCount($regulation->id);

        if (! Storage::disk('public')->exists($regulation->file_path)) {
            abort(404, 'File not found');
        }

        return response()->file(
            Storage::disk('public')->path($regulation->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.basename($regulation->file_path).'"',
            ]
        );
    })->name('regulation.download');
});

// Public Attendee Signing Page (Berita Acara - Individual)
Route::get('/berita-acara/sign/{token}', AttendeeSign::class)
    ->name('berita-acara.sign');

// Public Attendance List (Daftar Hadir - Master Link for Meeting)
Route::get('/berita-acara/attendance/{token}', PublicAttendance::class)
    ->name('berita-acara.attendance');

// Fallback or Admin Routes (Filament usually registers its own, but we keep the existing closures for safety)
// The previous routes were global, we should probably keep them accessible or restrict them?
// The previous code had:
/*
Route::get('/regulation-preview/{path}', ...);
Route::get('/clients/{client}/ticket/download', ...);
*/
// We'll keep them outside the domain group so they work on the admin domain too (datakkprl)

Route::get('/regulation-preview/{path}', function ($path) {
    if (! Str::startsWith($path, 'regulations/')) {
        abort(403, 'Invalid Path');
    }

    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response()->file(
        Storage::disk('public')->path($path),
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]
    );
})->where('path', '.*')->name('regulation.preview');

Route::get('/private-files/{path}', function (string $path) {
    abort_unless(auth()->check(), 403);

    return app(PrivateFileService::class)->response($path);
})->where('path', '.*')->name('private-files.admin');

Route::get('/clients/{client}/files/{path}', function (Request $request, Client $client, string $path) {
    if (! $client->matchesAccessToken($request->query('token')) && ! auth()->check()) {
        abort(403, 'Unauthorized');
    }

    $privateFiles = app(PrivateFileService::class);
    abort_unless($privateFiles->clientOwnsPath($client, $path), 404);

    return $privateFiles->response($path);
})->where('path', '.*')->name('client.files.download');

Route::get('/clients/{client}/ticket/download', function (Client $client) {
    $pdf = Pdf::loadView('pdf.ticket', compact('client'));
    $pdf->setPaper('a4', 'portrait');

    return $pdf->stream('Ticket-'.$client->ticket_number.'.pdf');
})->name('client.ticket.download');

Route::get('/clients/{client}/report/download', function (Request $request, Client $client) {
    if (! $client->matchesAccessToken($request->query('token')) && ! auth()->check()) {
        abort(403, 'Unauthorized');
    }

    $report = $client->latestConsultationReport;
    if (! $report) {
        abort(404, 'Belum ada laporan konsultasi.');
    }

    $pdf = Pdf::loadView('pdf.consultation-report', compact('client', 'report'));
    $pdf->setPaper('a4', 'portrait');

    return $pdf->stream('Laporan-Konsultasi-'.$client->ticket_number.'.pdf');
})->name('client.report.download');

Route::get('/clients/{client}/berita-acara/download', function (Request $request, Client $client) {
    if (! $client->matchesAccessToken($request->query('token')) && ! auth()->check()) {
        abort(403, 'Unauthorized');
    }

    $beritaAcara = $client->beritaAcara()->with('attendees')->first();

    if (! $beritaAcara) {
        abort(404, 'Belum ada berita acara.');
    }

    if (! auth()->check() && $beritaAcara->status !== 'completed') {
        abort(404, 'Berita acara belum selesai.');
    }

    if (! auth()->check() && ! $client->hasSatisfactionFeedback()) {
        return redirect()->route('check-status', [
            'ticket' => $client->ticket_number,
            'token' => $client->access_token,
        ]);
    }

    $signatureService = app(SignatureService::class);

    $pdf = Pdf::loadView('pdf.berita-acara', [
        'beritaAcara' => $beritaAcara,
        'client' => $client->load(['service', 'schedules.assignments.user', 'consultationLocation']),
        'signatureService' => $signatureService,
    ]);

    $pdf->setPaper('a4');

    $filename = 'Berita-Acara-'.($beritaAcara->nomor_berita_acara ?: $client->ticket_number).'.pdf';
    $filename = str_replace(['/', '\\'], '-', $filename);

    return $pdf->stream($filename);
})->name('client.berita-acara.download');
