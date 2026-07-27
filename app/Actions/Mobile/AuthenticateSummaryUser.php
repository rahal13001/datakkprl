<?php

namespace App\Actions\Mobile;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateSummaryUser
{
    public function handle(string $email, string $password): User
    {
        try {
            $response = Http::timeout(config('services.summary.timeout'))
                ->acceptJson()
                ->post(config('services.summary.login_url'), [
                    'email' => $email,
                    'password' => $password,
                ]);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'email' => ['The identity service is temporarily unavailable.'],
            ]);
        }

        $apiUser = $response->json('data.user');

        if (! $response->successful() || ! is_array($apiUser) || empty($apiUser['email'])) {
            throw ValidationException::withMessages([
                'email' => ['The supplied credentials are invalid.'],
            ]);
        }

        $user = User::firstOrNew(['email' => $apiUser['email']]);
        if (! $user->exists) {
            $user->password = Hash::make(Str::random(64));
        }
        $user->fill([
            'name' => $apiUser['name'] ?? $apiUser['email'],
            'email_verified_at' => $apiUser['email_verified_at'] ?? null,
            'summary_user_id' => $apiUser['id'] ?? null,
            'avatar_url' => $apiUser['avatar_url'] ?? null,
            'nip' => $apiUser['nip'] ?? null,
            'jabatan' => $apiUser['jabatan'] ?? null,
            'instansi' => $apiUser['instansi'] ?? null,
            'status' => (bool) ($apiUser['status'] ?? false),
        ]);
        $user->save();

        return $user;
    }
}
