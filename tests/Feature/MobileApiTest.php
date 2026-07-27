<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function app_configuration_is_public_and_reports_build_compatibility(): void
    {
        config()->set('mobile.android.latest_build', 8);
        config()->set('mobile.android.minimum_build', 5);

        $this->getJson('/api/mobile/v1/app-config?build=4')
            ->assertOk()
            ->assertJsonPath('data.api_version', '1.0')
            ->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.update_required', true)
            ->assertHeader('X-Request-Id');

        $this->getJson('/api/mobile/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    #[Test]
    public function summary_login_issues_a_sanctum_token_for_a_locally_authorized_user(): void
    {
        $user = User::factory()->create([
            'email' => 'officer@example.test',
            'status' => true,
        ]);
        $user->givePermissionTo(Permission::findOrCreate('ViewAny:Client'));

        Http::fake([
            '*' => Http::response([
                'data' => [
                    'user' => [
                        'id' => 456,
                        'name' => 'Authorized Officer',
                        'email' => 'officer@example.test',
                        'status' => 1,
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'officer@example.test',
            'password' => 'secret-password',
            'installation_id' => 'test-installation',
            'device_name' => 'Android Test Device',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'build_number' => 1,
            'notification_permission' => 'granted',
        ]);
        $response->assertOk()
            ->assertJsonPath('data.user.email', 'officer@example.test')
            ->assertJsonPath('data.capabilities.clients.list', true)
            ->assertJsonStructure(['data' => ['token', 'expires_at', 'device_id']]);

        $this->withToken($response->json('data.token'))
            ->putJson('/api/mobile/v1/me/device', [
                'installation_id' => 'test-installation',
                'device_name' => 'Android Test Device',
                'platform' => 'android',
                'app_version' => '1.0.0',
                'build_number' => 1,
                'notification_permission' => 'granted',
                'push_registration' => 'private-fcm-registration-value',
            ])
            ->assertOk()
            ->assertJsonPath('data.registered', true);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'installation_id' => 'test-installation',
            'platform' => 'android',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $device = UserDevice::query()->sole();
        $this->assertSame('private-fcm-registration-value', $device->push_registration);
        $this->assertStringNotContainsString(
            'private-fcm-registration-value',
            $device->getRawOriginal('push_registration_encrypted'),
        );
    }

    #[Test]
    public function a_user_with_only_the_inbox_default_does_not_receive_mobile_access(): void
    {
        User::factory()->create([
            'email' => 'unassigned@example.test',
            'status' => true,
        ]);

        Http::fake([
            '*' => Http::response([
                'data' => [
                    'user' => [
                        'id' => 789,
                        'name' => 'Unassigned User',
                        'email' => 'unassigned@example.test',
                        'status' => 1,
                    ],
                ],
            ]),
        ]);

        $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'unassigned@example.test',
            'password' => 'secret-password',
            'installation_id' => 'unassigned-installation',
            'device_name' => 'Android Test Device',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'build_number' => 1,
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'mobile_access_not_assigned');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function protected_mobile_routes_enforce_shield_permissions(): void
    {
        $user = User::factory()->create(['status' => true]);
        $permission = Permission::findOrCreate('ViewAny:Client');
        $user->givePermissionTo(Permission::findOrCreate('View:Client'));
        Sanctum::actingAs($user, ['mobile'], 'web');

        $forbidden = $this->getJson('/api/mobile/v1/clients');
        $forbidden->assertForbidden();
        $this->assertSame('forbidden', $forbidden->json('code'), $forbidden->getContent());

        DB::table(config('permission.table_names.model_has_permissions'))->insert([
            'permission_id' => $permission->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $user->unsetRelation('permissions');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->getJson('/api/mobile/v1/clients')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['per_page', 'next_cursor']]);
    }

    #[Test]
    public function stale_mobile_updates_are_rejected_without_mutating_the_record(): void
    {
        $user = User::factory()->create(['status' => true]);
        $user->givePermissionTo(Permission::findOrCreate('Update:Client'));
        Sanctum::actingAs($user, ['mobile'], 'web');

        $service = Service::create(['name' => 'Mobile API Test Service']);
        $client = Client::create([
            'service_id' => $service->id,
            'name' => 'Applicant',
            'email' => 'applicant@example.test',
            'whatsapp' => '08123456789',
        ]);

        $this->patchJson("/api/mobile/v1/clients/{$client->ticket_number}", [
            'version' => '2000-01-01T00:00:00.000000+00:00',
            'status' => 'completed',
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'record_changed');

        $this->assertSame('waiting', $client->fresh()->status);
    }
}
