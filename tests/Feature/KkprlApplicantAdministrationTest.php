<?php

namespace Tests\Feature;

use App\Models\KkprlApplicant;
use App\Models\KkprlApplicantEvent;
use App\Models\KkprlProposal;
use App\Models\User;
use App\Services\KkprlApplicantAdministrationService;
use App\Services\KkprlApplicantAuthService;
use Database\Seeders\KkprlApplicantPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KkprlApplicantAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(): KkprlApplicant
    {
        return app(KkprlApplicantAuthService::class)->register(['email' => 'account@example.test', 'password' => 'Old-password-123!', 'password_confirmation' => 'Old-password-123!']);
    }

    private function staff(string $permission): User
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $actor;
    }

    public function test_permission_seeder_grants_only_agreed_roles(): void
    {
        foreach (['admin', 'pimpinan', 'super_admin', 'petugas', 'panel_user'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $this->seed(KkprlApplicantPermissionSeeder::class);
        foreach (['admin', 'pimpinan', 'super_admin'] as $role) {
            $this->assertTrue(Role::findByName($role)->hasPermissionTo('Approve:KkprlApplicant'));
            $this->assertTrue(Role::findByName($role)->hasPermissionTo('ResetPassword:KkprlApplicant'));
        }
        $this->assertFalse(Role::findByName('petugas')->hasPermissionTo('Approve:KkprlApplicant'));
        $this->assertFalse(Role::findByName('panel_user')->hasPermissionTo('ResetPassword:KkprlApplicant'));
    }

    public function test_edit_approval_permission_does_not_approve_accounts(): void
    {
        $actor = $this->staff('ApproveEdit:KkprlProposal');
        $this->expectException(AuthorizationException::class);
        app(KkprlApplicantAdministrationService::class)->setApproval($this->applicant(), $actor, true);
    }

    public function test_approval_and_revocation_are_audited_and_distinct_from_staff_status(): void
    {
        $account = $this->applicant();
        $actor = $this->staff('Approve:KkprlApplicant');
        $service = app(KkprlApplicantAdministrationService::class);
        $service->setApproval($account, $actor, true);
        $this->assertNotNull($account->fresh()->approved_at);
        $this->assertSame($actor->id, $account->fresh()->approved_by);
        $service->setApproval($account, $actor, false);
        $this->assertNull($account->fresh()->approved_at);
        $this->assertSame(['approved', 'revoked'], $account->events()->orderBy('id')->pluck('action')->all());
        $this->assertTrue($actor->fresh()->status);
    }

    public function test_admin_reset_invalidates_password_and_existing_session_without_secret_audit(): void
    {
        $account = $this->applicant();
        $auth = app(KkprlApplicantAuthService::class);
        $auth->login($account->email, 'Old-password-123!');
        $actor = $this->staff('ResetPassword:KkprlApplicant');
        app(KkprlApplicantAdministrationService::class)->resetPassword($account, $actor, 'New-password-456!', 'New-password-456!');
        $this->assertFalse(Hash::check('Old-password-123!', $account->fresh()->password));
        $this->assertTrue(Hash::check('New-password-456!', $account->fresh()->password));
        $this->assertNull($auth->current());
        $audit = json_encode(KkprlApplicantEvent::first()->getAttributes());
        $this->assertStringNotContainsString('password-456', $audit);
        $this->assertStringNotContainsString($account->fresh()->password, $audit);
        $this->assertSame('password_reset', KkprlApplicantEvent::first()->action);
        $auth->login($account->email, 'New-password-456!');
        $this->assertTrue($auth->current()->is($account));
    }

    public function test_view_permission_does_not_reset_and_cannot_target_staff(): void
    {
        $actor = $this->staff('ViewAny:KkprlApplicant');
        $this->expectException(AuthorizationException::class);
        app(KkprlApplicantAdministrationService::class)->resetPassword($this->applicant(), $actor, 'New-password-456!', 'New-password-456!');
    }

    public function test_owner_assignment_preserves_locked_data_and_all_revisions(): void
    {
        $account = $this->applicant();
        $actor = $this->staff('AssignOwner:KkprlApplicant');
        $root = KkprlProposal::create(['status' => 'submitted', 'payload' => ['bag-1' => ['applicant_name' => 'Legacy']]]);
        $revision = KkprlProposal::create(['root_proposal_id' => $root->id, 'revision_of_id' => $root->id, 'ticket_number' => $root->ticket_number, 'revision_number' => 1]);
        $before = $root->fresh()->getAttributes();
        app(KkprlApplicantAdministrationService::class)->assignOwner($account, $root, $actor, 'Dokumen lama diperiksa bersama pemohon.', 'Verifikasi manual berkas proposal tanggal 2026-10-07');
        $this->assertSame($account->id, $root->fresh()->applicant_id);
        $this->assertSame($account->id, $revision->fresh()->applicant_id);
        unset($before['applicant_id']);
        $after = $root->fresh()->getAttributes();
        unset($after['applicant_id']);
        $this->assertSame($before, $after);
        $this->assertSame('owner_assigned', $account->events()->first()->action);
    }

    public function test_owner_assignment_never_overwrites_another_account(): void
    {
        $account = $this->applicant();
        $other = KkprlApplicant::create(['email' => 'other@example.test', 'password' => 'Other-password-123!']);
        $root = KkprlProposal::create([]);
        $root->forceFill(['applicant_id' => $other->id])->save();
        $this->expectException(AuthorizationException::class);
        app(KkprlApplicantAdministrationService::class)->assignOwner($account, $root, $this->staff('AssignOwner:KkprlApplicant'), 'Alasan diperiksa.', 'Referensi manual');
    }

    public function test_reset_cannot_accept_a_staff_model_even_with_reset_permission(): void
    {
        $actor = $this->staff('ResetPassword:KkprlApplicant');
        $target = User::factory()->create();
        $before = $target->password;
        try {
            app(KkprlApplicantAdministrationService::class)->resetPassword($target, $actor, 'New-password-456!', 'New-password-456!');
            $this->fail('Staff target accepted.');
        } catch (\TypeError) {
            $this->assertSame($before, $target->fresh()->password);
            $this->assertDatabaseCount('kkprl_applicant_events', 0);
        }
    }
}
