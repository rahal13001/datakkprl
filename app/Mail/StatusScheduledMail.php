<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusScheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jadwal Konsultasi Telah Ditetapkan - ' . $this->client->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.status_scheduled',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
