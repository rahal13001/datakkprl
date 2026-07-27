<?php

namespace App\Observers;

use App\Mail\ClientCreated;
use App\Mail\ClientUpdated;
use App\Models\Client;
use App\Services\MobileNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            Log::error('Failed to send ClientCreated email.', [
                'client_id' => $client->id,
                'exception' => get_class($e),
            ]);
        }

        app(MobileNotificationService::class)->newRequest($client);
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
            Log::error('Failed to send ClientUpdated email.', [
                'client_id' => $client->id,
                'exception' => get_class($e),
            ]);
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
