<?php

namespace Tests\Feature;

use App\Filament\Layanankkprl\Resources\KkprlApplicants\KkprlApplicantResource;
use App\Filament\Layanankkprl\Resources\KkprlApplicants\Pages\ListKkprlApplicants;
use App\Models\KkprlApplicant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KkprlApplicantFilamentTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $permissions): User
    {
        config(['app.env' => 'local']);
        $actor = User::factory()->create();
        foreach ($permissions as $permission) {
            $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($actor, 'web');
        Filament::setCurrentPanel(Filament::getPanel('layanankkprl'));

        return $actor;
    }

    public function test_without_permission_list_detail_navigation_and_direct_livewire_are_denied(): void
    {
        $this->staff([]);
        $account = KkprlApplicant::create(['email' => 'private@example.test', 'password' => 'Test-password-123!']);
        $this->assertFalse(KkprlApplicantResource::shouldRegisterNavigation());
        $this->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-applicants')->assertForbidden();
        $this->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-applicants/'.$account->id)->assertForbidden();
        Livewire::test(ListKkprlApplicants::class)->assertForbidden();
    }

    public function test_list_permission_does_not_allow_detail_approval_or_reset(): void
    {
        $this->staff(['ViewAny:KkprlApplicant']);
        $account = KkprlApplicant::create(['email' => 'limited@example.test', 'password' => 'Test-password-123!']);
        $this->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-applicants')->assertOk();
        $this->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-applicants/'.$account->id)->assertForbidden();
        Livewire::test(ListKkprlApplicants::class)
            ->assertTableActionHidden('approve', $account)
            ->assertTableActionHidden('resetPassword', $account)
            ->call('mountAction', 'approve', [], ['table' => true, 'recordKey' => (string) $account->id])
            ->call('callMountedAction');
        $this->assertNull($account->fresh()->approved_at);
    }

    public function test_direct_actions_without_their_permission_cannot_mutate_or_audit(): void
    {
        $this->staff(['ViewAny:KkprlApplicant']);
        $account = KkprlApplicant::create(['email' => 'direct@example.test', 'password' => 'Test-password-123!']);
        $before = $account->fresh()->getAttributes();
        foreach (['approve', 'revoke', 'resetPassword', 'assignOwner'] as $action) {
            Livewire::test(ListKkprlApplicants::class)
                ->call('mountAction', $action, [], ['table' => true, 'recordKey' => (string) $account->id])
                ->call('callMountedAction');
            $this->assertSame($before, $account->fresh()->getAttributes());
            $this->assertDatabaseCount('kkprl_applicant_events', 0);
        }
        $this->assertNull($account->fresh()->approved_at);
        $this->assertSame(1, $account->fresh()->session_version);
    }

    public function test_authorized_staff_can_approve_and_reset_through_filament(): void
    {
        $this->staff(['ViewAny:KkprlApplicant', 'View:KkprlApplicant', 'Approve:KkprlApplicant', 'ResetPassword:KkprlApplicant']);
        $account = KkprlApplicant::create(['email' => 'managed@example.test', 'password' => 'Test-password-123!']);
        $this->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-applicants/'.$account->id)->assertOk()->assertSee('managed@example.test');
        Livewire::test(ListKkprlApplicants::class)->callTableAction('approve', $account);
        $this->assertNotNull($account->fresh()->approved_at);
        Livewire::test(ListKkprlApplicants::class)->callTableAction('resetPassword', $account, data: ['password' => 'Changed-password-123!', 'password_confirmation' => 'Changed-password-123!']);
        $this->assertSame(2, $account->fresh()->session_version);
    }
}
