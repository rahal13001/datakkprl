<?php

// Fixed view path
namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tiket Konsultasi Anda: ' . $this->client->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client_created',
            with: [
                'url' => route('check-status', [
                    'ticket' => $this->client->ticket_number,
                    'token' => $this->client->access_token
                ]),
                'pdf_download_url' => route('client.ticket.download', $this->client),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
