<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_landing_page_displays_equal_hero_actions_and_small_scale_proposal_guide(): void
    {
        $guideUrl = 'https://kawanruanglaut.timurbersinar.com/belajar-kkprl/materi/panduan-perolehan-data-oseanografi-ekosistem-pesisir-batimetri-dan-pemetaan-dasar-bagi-sektor-perikanan-skala-kecil-serta-tingkat-resiko-rendah?access_uuid=7e7441a4-08f7-4aed-a473-64f8bcc396da';

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Buat Janji Temu',
                'Asistensi/Konsultasi Proposal',
                'Belajar KKPRL',
                'Arsip Regulasi KKPRL',
                'Pedoman Perolehan Data',
                'Proposal KKPRL Skala Kecil',
            ])
            ->assertSee('class="hero-action', false)
            ->assertSee('href="'.e($guideUrl).'"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('aria-label="Pedoman Perolehan Data Proposal KKPRL Skala Kecil (dibuka di tab baru)"', false)
            ->assertDontSee('id="belajar-highlight"', false);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());

        $heroActions = (new \DOMXPath($document))->query(
            '//a[contains(concat(" ", normalize-space(@class), " "), " hero-action ")]',
        );

        $this->assertCount(4, $heroActions);

        $heroActionIcons = (new \DOMXPath($document))->query(
            '//a[contains(concat(" ", normalize-space(@class), " "), " hero-action ")]//i[contains(concat(" ", normalize-space(@class), " "), " fa-solid ")]',
        );

        $this->assertCount(4, $heroActionIcons);
    }
}
