<?php

namespace App\Services;

use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

class ClientDocumentArchiveService
{
    /** @param Collection<int, Client> $clients */
    public function download(Collection $clients, bool $includeReports, bool $includeBeritaAcara): ?StreamedResponse
    {
        Gate::authorize('viewAny', Client::class);
        foreach ($clients as $client) {
            Gate::authorize('view', $client);
        }

        $clients->loadMissing([
            'service', 'schedules.assignments.user', 'assignments.user', 'consultationLocation',
            'latestConsultationReport.signer', 'beritaAcara.attendees',
        ]);
        $clients = $clients->unique('id')->filter(fn (Client $client): bool => ($includeReports && $client->latestConsultationReport !== null)
            || ($includeBeritaAcara && $client->beritaAcara !== null)
        );
        if ($clients->isEmpty()) {
            return null;
        }

        $directory = Storage::disk('local')->path('client-document-downloads/'.Str::uuid());
        $path = $directory.'/documents.zip';
        $archive = new ZipArchive;

        try {
            File::ensureDirectoryExists($directory, 0700);
            if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the client document archive.');
            }

            try {
                foreach ($clients as $client) {
                    if ($includeReports && $client->latestConsultationReport) {
                        $name = $this->filename('Laporan-Konsultasi', $client->ticket_number, $client);
                        $this->addPdf($archive, $directory, 'Laporan/'.$name, 'pdf.consultation-report', [
                            'client' => $client,
                            'report' => $client->latestConsultationReport,
                        ]);
                    }
                    if ($includeBeritaAcara && $client->beritaAcara) {
                        $name = $this->filename('Berita-Acara', $client->beritaAcara->nomor_berita_acara ?: $client->ticket_number, $client);
                        $this->addPdf($archive, $directory, 'Berita-Acara/'.$name, 'pdf.berita-acara', [
                            'client' => $client,
                            'beritaAcara' => $client->beritaAcara,
                            'signatureService' => app(SignatureService::class),
                        ]);
                    }
                }
            } finally {
                // ZipArchive reads the PDF files when closing; keep them until this point.
                if (! $archive->close()) {
                    throw new RuntimeException('Unable to finalize the client document archive.');
                }
            }

            $prefix = $includeReports && $includeBeritaAcara ? 'Dokumen-Klien' : ($includeReports ? 'Laporan-Konsultasi' : 'Berita-Acara');

            return response()->streamDownload(function () use ($path, $directory): void {
                try {
                    if (readfile($path) === false) {
                        throw new RuntimeException('Unable to read the client document archive.');
                    }
                } finally {
                    File::deleteDirectory($directory);
                }
            }, $prefix.'-'.now()->format('Ymd-His').'.zip', [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, private',
            ]);
        } catch (Throwable $exception) {
            File::deleteDirectory($directory);

            throw $exception;
        }
    }

    private function addPdf(ZipArchive $archive, string $directory, string $entry, string $view, array $data): void
    {
        $path = $directory.'/'.basename($entry);
        Pdf::loadView($view, $data)->setPaper('a4', 'portrait')->save($path);
        if (! $archive->addFile($path, $entry)) {
            throw new RuntimeException('Unable to add a PDF to the client document archive.');
        }
    }

    private function filename(string $prefix, string $identifier, Client $client): string
    {
        $identifier = Str::limit(Str::slug($identifier), 100, '') ?: 'klien';

        return $prefix.'-'.$identifier.'-'.$client->getKey().'.pdf';
    }
}
