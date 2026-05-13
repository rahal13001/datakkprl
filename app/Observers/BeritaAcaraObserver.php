<?php

namespace App\Observers;

use App\Mail\BeritaAcaraCompletedMail;
use App\Models\BeritaAcara;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BeritaAcaraObserver
{
    public function created(BeritaAcara $beritaAcara): void
    {
        if ($beritaAcara->status === 'completed') {
            $this->sendCompletedMail($beritaAcara);
        }
    }

    public function updated(BeritaAcara $beritaAcara): void
    {
        if ($beritaAcara->wasChanged('status') && $beritaAcara->status === 'completed') {
            $this->sendCompletedMail($beritaAcara);
        }
    }

    protected function sendCompletedMail(BeritaAcara $beritaAcara): void
    {
        $client = $beritaAcara->client;

        if (! $client?->email) {
            return;
        }

        try {
            Mail::to($client->email)->send(new BeritaAcaraCompletedMail($client, $beritaAcara));
            Log::info('BeritaAcaraCompletedMail sent.', [
                'client_id' => $client->id,
                'berita_acara_id' => $beritaAcara->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send BeritaAcaraCompletedMail.', [
                'client_id' => $client->id,
                'berita_acara_id' => $beritaAcara->id,
                'exception' => get_class($e),
            ]);
        }
    }
}
