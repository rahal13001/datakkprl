<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\PrivateFileService;
use App\Services\SignatureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class MobileFileController extends Controller
{
    public function file(Client $client, string $path, PrivateFileService $files): Response
    {
        $this->authorize('view', $client);
        abort_unless($files->clientOwnsPath($client, $path), 404);

        return $files->response($path);
    }

    public function ticket(Client $client): Response
    {
        $this->authorize('view', $client);

        return Pdf::loadView('pdf.ticket', compact('client'))
            ->setPaper('a4', 'portrait')
            ->stream("Ticket-{$client->ticket_number}.pdf");
    }

    public function report(Client $client): Response
    {
        $this->authorize('view', $client);
        $report = $client->latestConsultationReport;
        abort_unless($report, 404, 'No consultation report exists.');

        return Pdf::loadView('pdf.consultation-report', compact('client', 'report'))
            ->setPaper('a4', 'portrait')
            ->stream("Laporan-Konsultasi-{$client->ticket_number}.pdf");
    }

    public function beritaAcara(Client $client, SignatureService $signatureService): Response
    {
        $this->authorize('view', $client);
        $beritaAcara = $client->beritaAcara()->with('attendees')->firstOrFail();
        $client->load(['service', 'schedules.assignments.user', 'consultationLocation']);

        return Pdf::loadView('pdf.berita-acara', compact('beritaAcara', 'client', 'signatureService'))
            ->setPaper('a4')
            ->stream('Berita-Acara-'.str_replace(['/', '\\'], '-', $beritaAcara->nomor_berita_acara ?: $client->ticket_number).'.pdf');
    }
}
