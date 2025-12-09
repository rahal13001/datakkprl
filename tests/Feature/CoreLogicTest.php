<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Client;
use App\Models\Faq;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CoreLogicTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_auto_generates_ticket_and_uuid_for_client()
    {
        $service = Service::create(['name' => 'Test Service']);
        
        $client = Client::create([
            'contact_details' => ['name' => 'John Doe', 'email' => 'john@example.com'],
            'service_id' => $service->id,
        ]);

        $this->assertNotEmpty($client->ticket_number, 'Ticket number should be generated');
        $this->assertNotEmpty($client->access_token, 'UUID Access Token should be generated');
        $this->assertStringStartsWith('TICKET-', $client->ticket_number);
        $this->assertEquals('john@example.com', $client->email);
    }

    #[Test]
    public function it_auto_generates_slugs_for_master_data()
    {
        $service = Service::create(['name' => 'My Cool Service']);
        $this->assertEquals('my-cool-service', $service->slug);

        $faq = Faq::create([
            'question' => 'What is this?',
            'answer' => 'It is a test.',
        ]);
        $this->assertEquals('what-is-this', $faq->slug);
    }

    #[Test]
    public function iron_dome_logic_prevents_clash_with_15_min_buffer()
    {
        $service = new BookingAvailabilityService();
        $user = User::factory()->create(); // Needs UserFactory, assuming default exists

        // Create an existing schedule: 09:00 - 10:00
        $client = Client::create(['contact_details' => [], 'service_id' => Service::create(['name' => 'S1'])->id]);
        $schedule = Schedule::create([
            'client_id' => $client->id,
            'date' => '2025-12-10',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        Assignment::create([
            'schedule_id' => $schedule->id,
            'user_id' => $user->id,
            'status' => 'hadir'
        ]);

        // TEST 1: Exact Overlap (09:30 - 10:30) -> Should Fail
        $isAvailable = $service->checkOfficerAvailability($user->id, '2025-12-10', '09:30:00', '10:30:00');
        $this->assertFalse($isAvailable, 'Should reject exact overlap');

        // TEST 2: Buffer Clash (10:10 - 11:10) -> Should Fail (10:00 + 15m buffer = 10:15)
        // Existing ends 10:00. Buffer until 10:15. New starts 10:10.
        $isAvailable = $service->checkOfficerAvailability($user->id, '2025-12-10', '10:10:00', '11:10:00');
        $this->assertFalse($isAvailable, 'Should reject buffer 15 min violation');

        // TEST 3: Safe Slot (10:20 - 11:20) -> Should Pass (Starts after 10:15)
        $isAvailable = $service->checkOfficerAvailability($user->id, '2025-12-10', '10:20:00', '11:20:00');
        $this->assertTrue($isAvailable, 'Should accept safe slot');
    }

    #[Test]
    public function it_caches_faqs()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect(['cached_faq']));

        $service = new \App\Services\ContentDeliveryService();
        $result = $service->getFaqs();

        $this->assertEquals(['cached_faq'], $result->toArray());
    }

    #[Test]
    public function operational_hours_validation()
    {
        $service = new BookingAvailabilityService();
        $date = '2025-12-10'; // Wednesday (Weekday)

        // 7 AM -> Closed
        $this->assertFalse($service->validateOperationalHours($date, '07:00:00'));

        // 9 AM -> Open
        $this->assertTrue($service->validateOperationalHours($date, '09:00:00'));

        // 4 PM (16:00) -> Closed (Closes 15:00)
        $this->assertFalse($service->validateOperationalHours($date, '16:00:00'));
    }
}
