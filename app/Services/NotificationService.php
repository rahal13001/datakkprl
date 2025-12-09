<?php

namespace App\Services;

use App\Mail\BookingCreatedMail;
use App\Mail\BookingFinishedMail;
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
            'destination' => $client->email,
            'message_body' => $messageBody,
            'status' => substr($status, 0, 255), // Truncate if error too long
        ]);
    }
}
