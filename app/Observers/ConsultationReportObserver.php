<?php

namespace App\Observers;

use App\Models\ConsultationReport;
use App\Models\Client;
use App\Mail\StatusCompletedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ConsultationReportObserver
{
    /**
     * Handle the ConsultationReport "created" event.
     */
    public function created(ConsultationReport $report): void
    {
        $this->checkStatus($report);
    }

    /**
     * Handle the ConsultationReport "updated" event.
     */
    public function updated(ConsultationReport $report): void
    {
        $this->checkStatus($report);
    }

    /**
     * Check report status and update client if needed.
     */
    protected function checkStatus(ConsultationReport $report): void
    {
        // If report is marked as completed
        if ($report->status === 'completed') {
            $client = $report->client;
            
            // Only update if not already completed to avoid duplicate emails/updates
            if ($client && $client->status !== 'completed') {
                $client->update(['status' => 'completed']);
                
                // Send Email
                try {
                    if ($client->email) {
                        Mail::to($client->email)->send(new StatusCompletedMail($client));
                        Log::info('StatusCompletedMail sent.', [
                            'client_id' => $client->id,
                            'report_id' => $report->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send StatusCompletedMail.', [
                        'client_id' => $client->id,
                        'report_id' => $report->id,
                        'exception' => get_class($e),
                    ]);
                }
            }
        }
    }
}
