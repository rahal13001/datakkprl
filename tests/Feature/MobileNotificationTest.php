<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Services\MobileNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MobileNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mobile_notifications_are_after_commit_idempotent_and_privacy_safe(): void
    {
        Mail::fake();
        Queue::fake();
        $this->recipient();
        $service = Service::create(['name' => 'Rollback Notification Service']);

        try {
            DB::transaction(function () use ($service): void {
                Client::create([
                    'service_id' => $service->id,
                    'name' => 'Private Applicant',
                    'email' => 'private@example.test',
                    'whatsapp' => '08123456789',
                ]);

                throw new RuntimeException('Force rollback');
            });
        } catch (RuntimeException) {
            // Expected characterization of a rolled-back service transaction.
        }

        $this->assertDatabaseCount('notifications', 0);
        Queue::assertNothingPushed();

        $recipient = User::query()->firstOrFail();
        $client = Client::create([
            'service_id' => $service->id,
            'name' => 'Sensitive Applicant Name',
            'email' => 'sensitive@example.test',
            'whatsapp' => '081298765432',
        ]);

        app(MobileNotificationService::class)->newRequest($client);

        $this->assertDatabaseCount('notifications', 1);
        $notification = $recipient->notifications()->sole();
        $encoded = json_encode($notification->data);

        $this->assertSame('request.created', $notification->data['event_type']);
        $this->assertStringContainsString($client->ticket_number, $encoded);
        $this->assertStringNotContainsString('Sensitive Applicant Name', $encoded);
        $this->assertStringNotContainsString('sensitive@example.test', $encoded);
        $this->assertStringNotContainsString('081298765432', $encoded);
    }

    private function recipient(): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Client'));

        return $user;
    }
}
