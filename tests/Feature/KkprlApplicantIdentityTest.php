<?php

namespace Tests\Feature;

use App\Models\KkprlApplicant;
use App\Models\User;
use App\Services\KkprlApplicantAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KkprlApplicantIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_only_pending_account_and_ignores_privileged_input(): void
    {
        $account = app(KkprlApplicantAuthService::class)->register([
            'email' => ' PEMOHON@example.test ',
            'password' => 'Test-password-123!',
            'password_confirmation' => 'Test-password-123!',
            'approved_at' => now(),
            'approved_by' => 1,
            'role' => 'super_admin',
            'permissions' => ['Approve:KkprlApplicant'],
            'owner_id' => 1,
            'session_version' => 99,
        ]);

        $this->assertSame('pemohon@example.test', $account->email);
        $this->assertNull($account->approved_at);
        $this->assertNull($account->approved_by);
        $this->assertSame(1, $account->session_version);
        $this->assertTrue(Hash::check('Test-password-123!', $account->password));
        $this->assertArrayNotHasKey('password', $account->toArray());
        $this->assertDatabaseCount('kkprl_proposals', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
    }

    public function test_login_and_logout_keep_staff_guard_independent(): void
    {
        $staff = User::factory()->create();
        Auth::guard('web')->login($staff);
        $service = app(KkprlApplicantAuthService::class);
        $account = $service->register(['email' => 'one@example.test', 'password' => 'Test-password-123!', 'password_confirmation' => 'Test-password-123!']);
        $service->login('one@example.test', 'Test-password-123!');
        $this->assertTrue($service->current()->is($account));
        $this->assertTrue(Auth::guard('web')->user()->is($staff));
        $service->logout();
        $this->assertNull($service->current());
        $this->assertTrue(Auth::guard('web')->user()->is($staff));
    }

    public function test_stale_session_is_rejected_even_when_guard_cached_the_old_account(): void
    {
        $service = app(KkprlApplicantAuthService::class);
        $account = $service->register(['email' => 'two@example.test', 'password' => 'Test-password-123!', 'password_confirmation' => 'Test-password-123!']);
        $service->login($account->email, 'Test-password-123!');
        KkprlApplicant::whereKey($account->id)->increment('session_version');
        $this->assertNull($service->current());
        $this->assertFalse(Auth::guard('kkprl_applicant')->check());
    }

    public function test_staff_credentials_cannot_authenticate_as_applicant(): void
    {
        User::factory()->create(['email' => 'staff@example.test', 'password' => 'Test-password-123!']);
        $this->expectException(ValidationException::class);
        app(KkprlApplicantAuthService::class)->login('staff@example.test', 'Test-password-123!');
    }

    public function test_registration_requires_confirmed_password_and_unique_normalized_email(): void
    {
        $service = app(KkprlApplicantAuthService::class);
        $service->register(['email' => 'duplicate@example.test', 'password' => 'Test-password-123!', 'password_confirmation' => 'Test-password-123!']);
        $this->expectException(ValidationException::class);
        $service->register(['email' => 'DUPLICATE@example.test', 'password' => 'Test-password-123!', 'password_confirmation' => 'Test-password-123!']);
    }
}
