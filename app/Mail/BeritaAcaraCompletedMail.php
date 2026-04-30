<?php

namespace App\Mail;

use App\Models\BeritaAcara;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BeritaAcaraCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public BeritaAcara $beritaAcara,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Berita Acara Selesai - ' . $this->client->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.berita_acara_completed',
            with: [
                'client' => $this->client,
                'beritaAcara' => $this->beritaAcara,
                'downloadUrl' => route('client.berita-acara.download', [
                    'client' => $this->client->ticket_number,
                    'token' => $this->client->access_token,
                ]),
                'checkStatusUrl' => route('check-status', [
                    'ticket' => $this->client->ticket_number,
                    'token' => $this->client->access_token,
                ]),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
