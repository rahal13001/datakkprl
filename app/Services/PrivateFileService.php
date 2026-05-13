<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileService
{
    public function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        abort_if($path === '' || str_contains($path, '..') || str_starts_with($path, '/'), 403, 'Invalid file path.');

        return $path;
    }

    public function exists(string $path): bool
    {
        $path = $this->normalizePath($path);

        return Storage::disk('local')->exists($path) || Storage::disk('public')->exists($path);
    }

    public function get(string $path): ?string
    {
        $path = $this->normalizePath($path);

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->get($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->get($path);
        }

        return null;
    }

    public function response(string $path): StreamedResponse
    {
        $path = $this->normalizePath($path);

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->download($path, basename($path));
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, basename($path));
        }

        abort(404, 'File not found.');
    }

    public function clientOwnsPath(Client $client, string $path): bool
    {
        $path = $this->normalizePath($path);
        $ownedPaths = collect($client->supporting_documents ?? [])
            ->push($client->coordinate_file)
            ->merge($client->consultationReports()->pluck('documentation')->flatten())
            ->merge($client->beritaAcara ? [
                $client->beritaAcara->lampiran_peta,
                ...($client->beritaAcara->lampiran_dokumentasi ?? []),
                ...($client->beritaAcara->lampiran_lainnya ?? []),
            ] : [])
            ->filter()
            ->map(fn (string $ownedPath): string => $this->normalizePath($ownedPath));

        return $ownedPaths->contains($path);
    }
}
