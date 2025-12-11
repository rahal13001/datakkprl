<?php

namespace App\Services;

use App\Mail\BookingCreatedMail;
use App\Mail\BookingFinishedMail;
use App\Mail\ClientUpdated; // Import the Mailable
use App\Mail\RescheduleProposalMail;
use App\Models\Client;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;
use Exception;

class NotificationService
{
    /**
     * Send Booking Created Email
     */
    public function sendBookingCreated(Client $client)
    {
        try {
            Mail::to($client->email)->send(new BookingCreatedMail($client));
            $this->log($client, 'BookingCreatedMail', 'sent');
        } catch (Exception $e) {
            $this->log($client, 'BookingCreatedMail', 'failed: ' . $e->getMessage());
        }
    }

    /**
     * Send Client Updated Email
     */
    public function sendClientUpdated(Client $client)
    {
        try {
            Mail::to($client->email)->send(new ClientUpdated($client));
            $this->log($client, 'ClientUpdated', 'sent');
        } catch (Exception $e) {
            $this->log($client, 'ClientUpdated', 'failed: ' . $e->getMessage());
        }
    }

    /**
     * Send Reschedule Proposal
     */
    public function sendRescheduleProposal(Client $client, $newDate, $newTime)
    {
        try {
            Mail::to($client->email)->send(new RescheduleProposalMail($client, $newDate, $newTime));
            $this->log($client, 'RescheduleProposalMail', 'sent');
        } catch (Exception $e) {
            $this->log($client, 'RescheduleProposalMail', 'failed: ' . $e->getMessage());
        }
    }

    /**
     * Send Survey Link (Finished)
     */
    public function sendSurveyLink(Client $client)
    {
        try {
            Mail::to($client->email)->send(new BookingFinishedMail($client));
            $this->log($client, 'BookingFinishedMail', 'sent');
        } catch (Exception $e) {
            $this->log($client, 'BookingFinishedMail', 'failed: ' . $e->getMessage());
        }
    }

    /**
     * Log the notification attempt
     */
    protected function log(Client $client, $messageBody, $status)
    {
        NotificationLog::create([
            'client_id' => $client->id,
            'channel' => 'email',
            'destination' => $client->email ?? 'no-email', // Prevent null error
            'message_body' => $messageBody,
            'status' => substr($status, 0, 255), // Truncate if error too long
        ]);
    }
}
