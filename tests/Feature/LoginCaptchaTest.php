<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function invalid_captcha_blocks_login_before_external_api_request(): void
    {
        Http::fake();

        Livewire::test(Login::class)
            ->set('data.email', 'admin@example.test')
            ->set('data.password', 'password')
            ->set('data.captcha', 'wrong')
            ->call('authenticate')
            ->assertHasErrors(['data.captcha']);

        Http::assertNothingSent();
    }

    #[Test]
    public function valid_captcha_allows_external_api_login_request(): void
    {
        Http::fake([
            'summary.timurbersinar.com/*' => Http::response([
                'data' => [
                    'access_token' => 'external-api-token',
                    'user' => [
                        'id' => 123,
                        'email' => 'admin@example.test',
                        'name' => 'Admin User',
                        'email_verified_at' => now()->toISOString(),
                        'fcm_token' => null,
                        'avatar_url' => null,
                        'nip' => null,
                        'status' => '1',
                        'jabatan' => null,
                    ],
                ],
            ]),
        ]);

        $component = Livewire::test(Login::class);
        $captchaAnswer = session('login_captcha_answer');

        $component
            ->set('data.email', 'admin@example.test')
            ->set('data.password', 'password')
            ->set('data.captcha', $captchaAnswer)
            ->call('authenticate')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://summary.timurbersinar.com/api/login'
            && $request['email'] === 'admin@example.test'
            && $request['password'] === 'password');

        $this->assertAuthenticated();
    }
}
