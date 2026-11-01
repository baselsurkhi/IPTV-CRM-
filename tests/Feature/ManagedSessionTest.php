<?php

namespace Tests\Feature;

use App\Enums\DeviceStatus;
use App\Filament\Resources\Devices\Pages\EditDevice;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_xtream_server_address_is_saved_without_credentials_and_updated(): void
    {
        $this->login(['xtream_server_url' => 'https://user:secret@provider.example:8443/sub/player_api.php?username=private&password=secret#token']);
        $this->assertSame('https://provider.example:8443/sub/player_api.php', Device::first()->xtream_server_url);
        $this->login(['xtream_server_url' => 'http://other.example:8080']);
        $this->assertSame('http://other.example:8080', Device::first()->xtream_server_url);
        $this->login(['login_method' => 'm3u']);
        $this->assertNull(Device::first()->xtream_server_url);
    }

    public function test_xtream_server_rejects_non_http_urls(): void
    {
        $this->postJson('/api/v1/device/direct-session', $this->payload([
            'xtream_server_url' => 'javascript:alert(1)',
        ]))->assertUnprocessable()->assertJsonValidationErrors('xtream_server_url');
    }

    public function test_m3u_url_is_preserved_encrypted_and_visible_in_admin_form(): void
    {
        $url = 'https://provider.example/get.php?username=user&password=secret&type=m3u_plus';
        $this->login(['login_method' => 'm3u', 'm3u_source_url' => $url]);
        $device = Device::firstOrFail();
        $this->assertSame($url, $device->m3u_source_url);
        $this->assertStringNotContainsString('password=secret', $device->getRawOriginal('m3u_source_url'));
        $this->assertArrayNotHasKey('m3u_source_url', $device->toArray());
        $admin = User::factory()->create(['locale' => 'en']);
        $admin->assignRole('super-admin');
        $this->actingAs($admin)->get('/admin/device-registry/'.$device->id.'/edit')->assertOk()->assertSee('M3U playlist URL');
        Livewire::test(EditDevice::class, ['record' => $device->id])
            ->assertSchemaStateSet(['m3u_source_url' => $url]);
    }

    public function test_m3u_url_updates_and_clears_when_switching_method(): void
    {
        $this->login(['login_method' => 'm3u', 'm3u_source_url' => 'https://provider.example/first.m3u']);
        $this->login(['login_method' => 'm3u', 'm3u_source_url' => 'https://provider.example/new.m3u?token=abc']);
        $this->assertSame('https://provider.example/new.m3u?token=abc', Device::first()->m3u_source_url);
        $this->login();
        $this->assertNull(Device::first()->m3u_source_url);
    }

    public function test_m3u_url_rejects_non_http_schemes(): void
    {
        $this->postJson('/api/v1/device/direct-session', $this->payload([
            'login_method' => 'm3u', 'm3u_source_url' => 'javascript:alert(1)',
        ]))->assertUnprocessable()->assertJsonValidationErrors('m3u_source_url');
    }

    public function test_m3u_login_is_supported(): void
    {
        $this->login(['login_method' => 'm3u']);
        $this->assertSame('m3u', Device::first()->login_method);
    }

    public function test_installation_secret_cannot_be_replaced(): void
    {
        $this->login();
        $this->postJson('/api/v1/device/direct-session', $this->payload(['installation_secret' => str_repeat('b', 64)]))->assertForbidden()->assertJsonPath('code', 'installation_secret_mismatch');
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
        $this->postJson('/api/v1/device/direct-session', $this->payload())->assertForbidden()->assertJsonPath('code', 'device_deleted');
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
        $this->postJson('/api/v1/device/direct-session', $this->payload())->assertForbidden()->assertJsonPath('code', 'device_blocked');
        $this->assertDatabaseCount('devices', 1);
    }

    public function test_restore_access_repairs_both_records_and_preserves_credentials(): void
    {
        $this->login(['login_method' => 'm3u']);
        $managed = Device::firstOrFail();
        $hash = $managed->installation_secret_hash;
        $managed->update(['isdeleted' => true, 'status' => DeviceStatus::Rejected]);
        $activation = Device::create(['device_id' => $this->payload()['device_id'], 'registered_ip' => '127.0.0.1', 'status' => DeviceStatus::Blocked]);
        $other = Device::create(['device_id' => 'other-device', 'registered_ip' => '127.0.0.1', 'status' => DeviceStatus::Blocked]);

        $this->artisan('devices:restore-access', ['device_id' => $managed->device_id])->assertSuccessful();
        $this->assertTrue($managed->fresh()->isdeleted);
        $this->postJson('/api/v1/device/direct-session', $this->payload())->assertForbidden();

        $this->artisan('devices:restore-access', ['device_id' => $managed->device_id, '--apply' => true])->assertSuccessful();
        $this->assertSame($hash, $managed->fresh()->installation_secret_hash);
        $this->assertSame(DeviceStatus::Pending, $activation->fresh()->status);
        $this->assertNull($activation->fresh()->expires_at);
        $this->assertSame(DeviceStatus::Blocked, $other->fresh()->status);
        $this->login(['login_method' => 'm3u']);
    }

    public function test_rejected_device_returns_actionable_message(): void
    {
        $this->login();
        Device::first()->update(['status' => DeviceStatus::Rejected]);
        $response = $this->postJson('/api/v1/device/direct-session', $this->payload())
            ->assertForbidden()->assertJsonPath('code', 'device_rejected');
        $this->assertNotEmpty($response->json('message'));
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
        $this->login(['xtream_server_url' => 'https://provider.example:8443']);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Welcome back')->assertSee('Recently active devices');
        $this->get('/admin/device-registry')->assertOk()->assertSee('Living room');
        $this->get('/admin/device-registry/'.Device::first()->id.'/edit')->assertOk()->assertSee('Device details')->assertSee('Xtream server URL');
        Livewire::test(EditDevice::class, ['record' => Device::first()->id])
            ->assertSchemaStateSet(['xtream_server_url' => 'https://provider.example:8443']);
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
