<?php

namespace App\Services;

use App\Models\KkprlApplicant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class KkprlApplicantAuthService
{
    public const GUARD = 'kkprl_applicant';

    public const VERSION_KEY = 'kkprl_applicant_session_version';

    public function register(#[\SensitiveParameter] array $input): KkprlApplicant
    {
        $key = 'kkprl:register:'.hash('sha256', request()->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Coba lagi nanti.']);
        }
        RateLimiter::hit($key, 900);
        $input['email'] = $this->email($input['email'] ?? '');
        $validated = Validator::make($input, [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:kkprl_applicants,email'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)],
        ])->validate();

        return KkprlApplicant::create(['email' => $validated['email'], 'password' => $validated['password']])->fresh();
    }

    public function login(string $email, #[\SensitiveParameter] string $password): void
    {
        $email = $this->email($email);
        $key = 'kkprl:login:'.hash('sha256', $email.'|'.request()->ip());
        $ipKey = 'kkprl:login:ip:'.hash('sha256', request()->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Coba lagi nanti.']);
        }
        $guard = Auth::guard(self::GUARD);
        // Lock the account while checking credentials and binding the session version.
        // A concurrent reset must not authenticate the old password with the new version.
        $authenticated = DB::transaction(function () use ($guard, $email, $password): bool {
            $account = KkprlApplicant::where('email', $email)->lockForUpdate()->first();
            if (! $account || ! $guard->attempt(['email' => $email, 'password' => $password], false)) {
                return false;
            }
            session()->regenerate();
            session()->put(self::VERSION_KEY, $account->session_version);
            session()->forget(['kkprl_proposal_access', 'kkprl_selected_proposal']);

            return true;
        });
        if (! $authenticated) {
            RateLimiter::hit($key, 900);
            RateLimiter::hit($ipKey, 900);
            throw ValidationException::withMessages(['email' => 'Email atau password tidak valid.']);
        }
        RateLimiter::clear($key);
    }

    public function current(bool $lock = false): ?KkprlApplicant
    {
        $id = Auth::guard(self::GUARD)->id();
        if ($id === null) {
            return null;
        }
        $query = KkprlApplicant::whereKey($id);
        $account = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $account || session(self::VERSION_KEY) !== $account->session_version) {
            $this->logout();

            return null;
        }

        return $account;
    }

    public function logout(): void
    {
        Auth::guard(self::GUARD)->logout();
        session()->forget([self::VERSION_KEY, 'kkprl_proposal_access', 'kkprl_selected_proposal']);
        // Preserve the staff guard when both identities share one browser session.
        session()->regenerate(true);
    }

    private function email(mixed $email): string
    {
        return is_string($email) ? mb_strtolower(trim($email)) : '';
    }
}
