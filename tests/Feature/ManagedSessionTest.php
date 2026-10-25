<?php

namespace Tests\Feature;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagedSessionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'device_id' => '12345678-1234-4234-8234-123456789012',
            'installation_secret' => str_repeat('a', 64),
            'login_method' => 'xtream', 'device_name' => 'Living room',
            'platform' => 'android_tv', 'app_version' => '10.0.0+10',
            'metadata' => ['model' => 'Test TV', 'release' => '14'],
        ], $overrides);
    }

    private function login(array $overrides = []): string
    {
        return $this->postJson('/api/v1/device/direct-session', $this->payload($overrides))->assertOk()->json('access_token');
    }

    public function test_direct_login_tracks_metadata_without_an_activation_subscription(): void
    {
        $token = $this->login();
        $device = Device::firstOrFail();
        $this->assertSame('xtream', $device->login_method);
        $this->assertSame('Test TV', $device->device_metadata['model']);
        $this->assertNull($device->expires_at);
        $this->assertNull($device->iptv_password);
        $this->assertArrayNotHasKey('installation_secret_hash', $device->toArray());
        $this->withToken($token)->getJson('/api/v1/subscription/status')->assertOk()->assertJsonPath('status', 'active');
    }

    public function test_m3u_login_is_supported(): void
    {
        $this->login(['login_method' => 'm3u']);
        $this->assertSame('m3u', Device::first()->login_method);
    }

    public function test_installation_secret_cannot_be_replaced(): void
    {
        $this->login();
        $this->postJson('/api/v1/device/direct-session', $this->payload(['installation_secret' => str_repeat('b', 64)]))->assertForbidden();
        $this->assertSame(1, Device::first()->tokens()->count());
    }

    public function test_block_prevents_new_sessions_and_invalidates_existing_tokens(): void
    {
        $token = $this->login();
        Device::first()->update(['status' => DeviceStatus::Blocked]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->withToken($token)->getJson('/api/v1/subscription/status')->assertUnauthorized();
        $this->postJson('/api/v1/device/direct-session', $this->payload())->assertForbidden();
    }

    public function test_deleted_device_cannot_sign_in_again(): void
    {
        $this->login();
        Device::first()->update(['isdeleted' => true]);
        $this->postJson('/api/v1/device/direct-session', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_kick_allows_fresh_explicit_login(): void
    {
        $token = $this->login();
        Device::first()->revokeAllTokens();
        $this->withToken($token)->getJson('/api/v1/subscription/status')->assertUnauthorized();
        $this->login();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_rotation_keeps_only_one_token(): void
    {
        $this->login();
        $this->login();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_logout_revokes_token(): void
    {
        $token = $this->login();
        $this->withToken($token)->deleteJson('/api/v1/device/session')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_blocked_activation_cannot_bypass_by_switching_to_direct(): void
    {
        Device::create(['device_id' => $this->payload()['device_id'], 'registered_ip' => '127.0.0.1', 'status' => DeviceStatus::Blocked]);
        $this->postJson('/api/v1/device/direct-session', $this->payload())->assertForbidden();
    }

    public function test_direct_record_cannot_request_provider_credentials(): void
    {
        $this->login();
        $this->postJson('/api/v1/device/session', ['device_id' => Device::first()->device_id, 'ip_address' => '127.0.0.1'])->assertNotFound();
    }

    public function test_crm_pages_render_for_authorized_admin(): void
    {
        $admin = User::factory()->create(['locale' => 'en']);
        $admin->assignRole('super-admin');
        $this->login();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Welcome back')->assertSee('Recently active devices');
        $this->get('/admin/device-registry')->assertOk()->assertSee('Living room');
        $this->get('/admin/device-registry/'.Device::first()->id.'/edit')->assertOk()->assertSee('Device details');
    }

    public function test_viewer_cannot_modify_devices(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        $this->login();
        $this->assertFalse($viewer->can('update', Device::first()));
        $this->assertFalse($viewer->can('delete', Device::first()));
    }
}
