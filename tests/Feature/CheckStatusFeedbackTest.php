<?php

namespace Tests\Feature;

use App\Livewire\CheckStatus;
use App\Models\Client;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckStatusFeedbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function estimated_cost_savings_is_required_and_saved_with_client_feedback(): void
    {
        $service = Service::create(['name' => 'Layanan Pengujian']);
        $client = Client::create([
            'contact_details' => [],
            'name' => 'Pemohon Pengujian',
            'email' => 'pemohon@example.test',
            'whatsapp' => '081234567890',
            'service_id' => $service->id,
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
}
