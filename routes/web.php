<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/regulation-preview/{path}', function ($path) {
    \Illuminate\Support\Facades\Log::info("Preview requested for path: " . $path);
    
    // Security: Only allow access to regulations folder
    if (! Illuminate\Support\Str::startsWith($path, 'regulations/')) {
        \Illuminate\Support\Facades\Log::error("Preview blocked: Path does not start with regulations/");
        abort(403, 'Invalid Path');
    }

    if (! Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        \Illuminate\Support\Facades\Log::error("Preview failed: File not found at " . $path);
        abort(404);
    }

    return response()->file(
        Illuminate\Support\Facades\Storage::disk('public')->path($path),
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]
    );
})->where('path', '.*')->name('regulation.preview');

Route::get('/clients/{client}/ticket/download', function (\App\Models\Client $client) {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ticket', compact('client'));
    $pdf->setPaper('a4', 'portrait');
    
    return $pdf->stream('Ticket-' . $client->ticket_number . '.pdf');
})->name('client.ticket.download');
