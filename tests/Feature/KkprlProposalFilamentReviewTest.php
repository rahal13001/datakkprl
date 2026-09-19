<?php

namespace Tests\Feature;

use App\Models\KkprlProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KkprlProposalFilamentReviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authorized_reviewer_can_open_proposal_and_approval_resources(): void
    {
        config(['app.env' => 'local']);
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));
        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-proposals')
            ->assertOk()
            ->assertSee('Review Proposal');

        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-proposal-edit-approvals')
            ->assertOk()
            ->assertSee('Approval Edit Darurat');
    }

    #[Test]
    public function user_without_review_permission_cannot_open_proposal_resource(): void
    {
        config(['app.env' => 'local']);
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));

        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-proposals')
            ->assertForbidden();
    }

    #[Test]
    public function approval_permission_alone_can_open_approval_resource(): void
    {
        config(['app.env' => 'local']);
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('ApproveEdit:KkprlProposal', 'web'));

        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-proposal-edit-approvals')
            ->assertOk()
            ->assertSee('Approval Edit Darurat');
    }

    #[Test]
    public function authorized_detail_renders_active_chapters_without_exposing_phone(): void
    {
        config(['app.env' => 'local']);
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('panel_user', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));
        $proposal = KkprlProposal::create([
            'phone' => '0812-3456-7890',
            'payload' => [
                'includes_reclamation' => true,
                'land_relation' => 'adjacent',
                'has_existing_permits' => true,
                'bag-1' => ['applicant_name' => 'Pemohon internal'],
            ],
        ]);

        $this->actingAs($user)
            ->get('http://kawanruanglaut.timurbersinar.com/layananruanglaut/kkprl-proposals/'.$proposal->id)
            ->assertOk()
            ->assertSee('Bab aktif')
            ->assertSee('Bab 4')
            ->assertSee('Bab 5')
            ->assertSee('Pemohon internal')
            ->assertDontSee('081234567890');
    }
}
