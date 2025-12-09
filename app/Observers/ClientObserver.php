<?php

namespace App\Observers;

use App\Mail\ClientCreated;
use App\Mail\ClientUpdated;
use App\Models\Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        try {
            if (isset($client->contact_details['email'])) {
                Mail::to($client->contact_details['email'])->send(new ClientCreated($client));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send ClientCreated email: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        // Only send email if important fields changed (status, service, schedule, etc)
        // For now, let's send on any update, but maybe filter to avoid spam.
        // User said: "when data is edited, an email will be sent ... whether it's a status change or a data change."
        
        try {
            if (isset($client->contact_details['email'])) {
                Mail::to($client->contact_details['email'])->send(new ClientUpdated($client));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send ClientUpdated email: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "force deleted" event.
     */
    public function forceDeleted(Client $client): void
    {
        //
    }
}
