<?php

namespace App\Mail;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffAssigned extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Assignment $assignment
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tugas Baru: Layanan Konsultasi KKPRL',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.staff.assigned',
            with: [
                'staffName' => $this->assignment->user->name,
                'clientName' => $this->assignment->schedule->client->contact_details['name'] ?? 'Klien',
                'serviceName' => $this->assignment->schedule->client->service->name ?? 'Layanan',
                'date' => \Carbon\Carbon::parse($this->assignment->schedule->date)->format('d M Y'),
                'time' => $this->assignment->schedule->start_time . ' - ' . $this->assignment->schedule->end_time,
                'isOnline' => $this->assignment->schedule->is_online,
                'meetingLink' => $this->assignment->schedule->meeting_link,
                'ticketNumber' => $this->assignment->schedule->client->ticket_number,
                'dashboardUrl' => \App\Filament\Layanankkprl\Resources\Clients\ClientResource::getUrl(
                    'view', 
                    ['record' => $this->assignment->schedule->client], 
                    panel: 'layanankkprl'
                ),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
