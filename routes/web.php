<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\LandingPage;
use App\Http\Controllers\RegulationController; // Assuming we might need this or use closure

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
    Route::get('/cek-status', \App\Livewire\CheckStatus::class)->name('check-status');
    
    // Regulation Preview/Download (Public)
    Route::get('/regulasi/{slug}', function ($slug) {
        $regulation = \App\Models\Regulation::where('slug', $slug)->firstOrFail();
        
        // Increment download count
        app(\App\Services\ContentDeliveryService::class)->incrementDownloadCount($regulation->id);

        if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($regulation->file_path)) {
            abort(404, 'File not found');
        }

        return response()->file(
            \Illuminate\Support\Facades\Storage::disk('public')->path($regulation->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($regulation->file_path) . '"',
            ]
        );
    })->name('regulation.download');
});

// Public Attendee Signing Page (Berita Acara - Individual)
Route::get('/berita-acara/sign/{token}', \App\Livewire\AttendeeSign::class)
    ->name('berita-acara.sign');

// Public Attendance List (Daftar Hadir - Master Link for Meeting)
Route::get('/berita-acara/attendance/{token}', \App\Livewire\PublicAttendance::class)
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
    if (! \Illuminate\Support\Str::startsWith($path, 'regulations/')) {
        abort(403, 'Invalid Path');
    }

    if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response()->file(
        \Illuminate\Support\Facades\Storage::disk('public')->path($path),
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]
    );
})->where('path', '.*')->name('regulation.preview');

Route::get('/private-files/{path}', function (string $path) {
    abort_unless(auth()->check(), 403);

    return app(\App\Services\PrivateFileService::class)->response($path);
})->where('path', '.*')->name('private-files.admin');

Route::get('/clients/{client}/files/{path}', function (\Illuminate\Http\Request $request, \App\Models\Client $client, string $path) {
    if (! $client->matchesAccessToken($request->query('token')) && ! auth()->check()) {
        abort(403, 'Unauthorized');
    }

    $privateFiles = app(\App\Services\PrivateFileService::class);
    abort_unless($privateFiles->clientOwnsPath($client, $path), 404);

    return $privateFiles->response($path);
})->where('path', '.*')->name('client.files.download');

Route::get('/clients/{client}/ticket/download', function (\App\Models\Client $client) {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ticket', compact('client'));
    $pdf->setPaper('a4', 'portrait');
    
    return $pdf->stream('Ticket-' . $client->ticket_number . '.pdf');
})->name('client.ticket.download');

Route::get('/clients/{client}/report/download', function (\Illuminate\Http\Request $request, \App\Models\Client $client) {
    if (! $client->matchesAccessToken($request->query('token')) && !auth()->check()) {
        abort(403, 'Unauthorized');
    }

    $report = $client->latestConsultationReport;
    if (! $report) {
         abort(404, 'Belum ada laporan konsultasi.');
    }

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.consultation-report', compact('client', 'report'));
    $pdf->setPaper('a4', 'portrait');
    
    return $pdf->stream('Laporan-Konsultasi-' . $client->ticket_number . '.pdf');
})->name('client.report.download');

Route::get('/clients/{client}/berita-acara/download', function (\Illuminate\Http\Request $request, \App\Models\Client $client) {
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

    $signatureService = app(\App\Services\SignatureService::class);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.berita-acara', [
        'beritaAcara' => $beritaAcara,
        'client' => $client->load(['service', 'schedules.assignments.user', 'consultationLocation']),
        'signatureService' => $signatureService,
    ]);

    $pdf->setPaper('a4');

    $filename = 'Berita-Acara-' . ($beritaAcara->nomor_berita_acara ?: $client->ticket_number) . '.pdf';
    $filename = str_replace(['/', '\\'], '-', $filename);

    return $pdf->stream($filename);
})->name('client.berita-acara.download');
