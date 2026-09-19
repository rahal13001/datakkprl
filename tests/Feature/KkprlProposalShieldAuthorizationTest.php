<?php

namespace Tests\Feature;

use App\Models\KkprlProposal;
use App\Models\User;
use Database\Seeders\KkprlPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KkprlProposalShieldAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function review_access_comes_from_permission_not_role_name(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('Review:KkprlProposal', 'web'));
        $proposal = KkprlProposal::create(['phone' => '0812-3456-7890']);

        $this->assertTrue($user->can('viewAny', KkprlProposal::class));
        $this->assertTrue($user->can('view', $proposal));
    }

    #[Test]
    public function user_without_review_permission_cannot_review(): void
    {
        $user = User::factory()->create();
        $proposal = KkprlProposal::create(['phone' => '0812-3456-7890']);

        $this->assertFalse($user->can('viewAny', KkprlProposal::class));
        $this->assertFalse($user->can('view', $proposal));
    }

    #[Test]
    public function kkprl_permission_seeder_registers_permission_catalog_without_roles(): void
    {
        $this->seed(KkprlPermissionSeeder::class);

        foreach ([
            'Review:KkprlProposal',
            'RequestEdit:KkprlProposal',
            'ApproveEdit:KkprlProposal',
            'RunEditSession:KkprlProposal',
            'Download:KkprlProposalDocument',
            'Download:KkprlProposalAttachment',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->assertDatabaseCount('roles', 0);
    }
}
