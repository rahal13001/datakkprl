<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;

class Login extends BaseLogin
{
    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        try {
            $response = Http::post('https://summary.timurbersinar.com/api/login', [
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            dd($response->json(), $response->status());

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
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
