<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RescheduleProposalMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public $newDate,
        public $newTime
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Usulan Jadwal Ulang Konsultasi: ' . $this->client->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.reschedule_proposal',
             with: [
                'acceptUrl' => url('/booking/negotiate/' . $this->client->ticket_number . '/accept?token=' . $this->client->access_token . '&date=' . $this->newDate . '&time=' . $this->newTime),
                'rejectUrl' => url('/booking/negotiate/' . $this->client->ticket_number . '/reject?token=' . $this->client->access_token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
