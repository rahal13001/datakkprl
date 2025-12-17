<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingFinishedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Konsultasi Selesai: ' . $this->client->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.finished',
             with: [
                'surveyUrl' => route('check-status', [
                    'ticket' => $this->client->ticket_number,
                    'token' => $this->client->access_token
                ]),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
