<?php

namespace Tests\Feature;

use App\Livewire\CheckStatus;
use App\Models\Client;
use App\Models\ConsultationLocation;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckStatusFeedbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function estimated_cost_savings_is_required_and_saved_with_client_feedback_when_location_requires_it(): void
    {
        $service = Service::create(['name' => 'Layanan Pengujian']);
        $location = ConsultationLocation::create([
            'name' => 'Online',
            'is_online' => true,
            'requires_cost_savings_estimate' => true,
        ]);

        $client = Client::create([
            'contact_details' => [],
            'name' => 'Pemohon Pengujian',
            'email' => 'pemohon@example.test',
            'whatsapp' => '081234567890',
            'service_id' => $service->id,
            'consultation_location_id' => $location->id,
            'status' => 'completed',
        ]);
        $accessToken = $client->access_token;

        $component = Livewire::test(CheckStatus::class)
            ->set('ticket_number', $client->ticket_number)
            ->set('access_token', $accessToken)
            ->call('check')
            ->set('criticism', 'Pelayanan sudah berjalan dengan baik.')
            ->set('suggestion', 'Pertahankan kemudahan akses layanan.');

        $component
            ->call('submitFeedback')
            ->assertHasErrors(['estimated_cost_savings' => 'required']);

        $component
            ->set('estimated_cost_savings', 1500000)
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('satisfaction_surveys', [
            'client_id' => $client->id,
            'estimated_cost_savings' => 1500000,
        ]);
    }

    #[Test]
    public function estimated_cost_savings_is_not_required_when_location_does_not_require_it(): void
    {
        $service = Service::create(['name' => 'Layanan Pengujian']);
        $location = ConsultationLocation::create([
            'name' => 'Sorong',
            'is_online' => false,
            'requires_cost_savings_estimate' => false,
        ]);

        $client = Client::create([
            'contact_details' => [],
            'name' => 'Pemohon Pengujian',
            'email' => 'pemohon@example.test',
            'whatsapp' => '081234567890',
            'service_id' => $service->id,
            'consultation_location_id' => $location->id,
            'status' => 'completed',
        ]);

        Livewire::test(CheckStatus::class)
            ->set('ticket_number', $client->ticket_number)
            ->set('access_token', $client->access_token)
            ->call('check')
            ->set('criticism', 'Pelayanan sudah berjalan dengan baik.')
            ->set('suggestion', 'Pertahankan kemudahan akses layanan.')
            ->set('estimated_cost_savings', 1500000)
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('satisfaction_surveys', [
            'client_id' => $client->id,
            'estimated_cost_savings' => null,
        ]);
    }
}
