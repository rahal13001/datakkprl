<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlPreflightTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function kkprl_private_disk_is_not_registered_as_a_served_storage_route(): void
    {
        $this->assertSame('kkprl_private', config('kkprl.storage_disk'));
        $this->assertFalse((bool) config('filesystems.disks.kkprl_private.serve'));
        $this->assertNull(app('router')->getRoutes()->getByName('storage.kkprl_private'));
    }

    #[Test]
    public function strict_preflight_passes_when_runtime_renderer_is_available(): void
    {
        config(['kkprl.pdf_renderer.binary' => PHP_BINARY]);

        $this->artisan('kkprl:preflight', ['--strict' => true])
            ->expectsOutputToContain('Private storage')
            ->expectsOutputToContain('Session cookie security')
            ->expectsOutputToContain('KKPRL templates')
            ->expectsOutputToContain('PDF attachment renderer')
            ->expectsOutputToContain('Image normalization')
            ->assertExitCode(0);
    }

    #[Test]
    public function strict_preflight_fails_when_session_cookie_security_is_disabled(): void
    {
        config([
            'kkprl.pdf_renderer.binary' => PHP_BINARY,
            'session.http_only' => false,
        ]);

        $this->artisan('kkprl:preflight', ['--strict' => true])
            ->expectsOutputToContain('FAIL Session cookie security')
            ->assertExitCode(1);
    }

    #[Test]
    public function strict_preflight_fails_when_renderer_is_missing(): void
    {
        config(['kkprl.pdf_renderer.binary' => base_path('missing-pdftoppm')]);

        $this->artisan('kkprl:preflight', ['--strict' => true])
            ->expectsOutputToContain('FAIL PDF attachment renderer')
            ->assertExitCode(1);
    }
}
