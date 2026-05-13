<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

    public string $captchaQuestion = '';

    public function mount(): void
    {
        $this->generateCaptchaChallenge();

        parent::mount();
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getCaptchaFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $this->validateCaptcha($data['captcha'] ?? null);

        try {
            $response = Http::post('https://summary.timurbersinar.com/api/login', [
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['data']['user'])) {
                    $apiUser = $responseData['data']['user'];
                    $token = $responseData['data']['access_token'] ?? null;

                    // Update or create user in local database
                    $user = User::updateOrCreate(
                        ['email' => $apiUser['email']],
                        [
                            'name' => $apiUser['name'],
                            'password' => bcrypt($data['password']), // Sync password or keep random
                            'email_verified_at' => $apiUser['email_verified_at'],
                            'summary_user_id' => $apiUser['id'], // Assuming migration added this
                            'fcm_token' => $apiUser['fcm_token'] ?? null,
                            'avatar_url' => $apiUser['avatar_url'] ?? null,
                            'nip' => $apiUser['nip'] ?? null,
                            'status' => $apiUser['status'] == '1',
                            'jabatan' => $apiUser['jabatan'] ?? null,
                            // 'custom_fields' => $apiUser['custom_fields'] ?? null, // Add if migration has it
                        ]
                    );

                    // Log the user in
                    Auth::login($user, $data['remember'] ?? false);

                    session()->regenerate();

                    return app(LoginResponse::class);
                }
            } else {
                $this->generateCaptchaChallenge();

                throw ValidationException::withMessages([
                    'data.email' => 'Login failed. Please check your credentials and try again.',
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->generateCaptchaChallenge();

            throw ValidationException::withMessages([
                'data.email' => 'Login failed. Please try again.',
            ]);
        }

        $this->generateCaptchaChallenge();

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getCaptchaFormComponent(): Component
    {
        return TextInput::make('captcha')
            ->label(fn (): string => "Security check: {$this->captchaQuestion}")
            ->numeric()
            ->required()
            ->autocomplete('off')
            ->dehydrated();
    }

    protected function generateCaptchaChallenge(): void
    {
        $left = random_int(2, 9);
        $right = random_int(2, 9);

        $this->captchaQuestion = "{$left} + {$right} = ?";
        session()->put('login_captcha_answer', (string) ($left + $right));

        if (isset($this->data['captcha'])) {
            $this->data['captcha'] = null;
        }
    }

    protected function validateCaptcha(mixed $answer): void
    {
        $expected = session('login_captcha_answer');

        if (is_string($expected) && hash_equals($expected, trim((string) $answer))) {
            session()->forget('login_captcha_answer');

            return;
        }

        $this->generateCaptchaChallenge();

        throw ValidationException::withMessages([
            'data.captcha' => 'The security check answer is incorrect.',
        ]);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Masuk')
            ->icon('heroicon-m-arrow-right-end-on-rectangle');
    }
}
