<?php

namespace Tests\Feature;

use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function englishApi(): array
    {
        return ['Accept-Language' => 'en'];
    }

    public function test_registers_device_as_pending_with_device_id(): void
    {
        $this->withHeaders($this->englishApi())
            ->postJson('/api/v1/device/register', [
                'device_id' => 'device-1777899551996',
                'ip_address' => '192.168.1.100',
            ])->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('locale', 'en');

        $this->assertDatabaseHas('devices', [
            'device_id' => 'device-1777899551996',
            'registered_ip' => '192.168.1.100',
            'status' => 'pending',
        ]);
    }

    public function test_session_returns_player_payload_when_approved(): void
    {
        Device::query()->create([
            'device_id' => 'device-approved-001',
            'registered_ip' => '10.0.0.1',
            'status' => DeviceStatus::Approved,
            'iptv_username' => 'u1',
            'iptv_password' => 'passw',
            'player_api_base_url' => null,
            'subscribed_at' => now(),
            'expires_at' => now()->addMonth(),
            'subscription_plan' => SubscriptionPlan::Standard,
        ]);

        $res = $this->withHeaders($this->englishApi())
            ->postJson('/api/v1/device/session', [
                'device_id' => 'device-approved-001',
                'ip_address' => '10.0.0.1',
            ]);

        $res->assertOk();
        $res->assertJsonStructure([
            'access_token',
            'player_api_url',
            'username',
            'password',
            'locale',
        ]);
        $res->assertJsonPath('username', 'u1');
        $res->assertJsonPath('locale', 'en');
        $this->assertStringContainsString('username=u1', $res->json('player_api_url'));
        $this->assertStringContainsString('password=', $res->json('player_api_url'));
        $res->assertJsonPath('subscription_status', 'active');
    }

    public function test_session_requires_an_active_subscription(): void
    {
        Device::query()->create([
            'device_id' => 'device-no-subscription-001',
            'registered_ip' => '10.0.0.2',
            'status' => DeviceStatus::Approved,
            'iptv_username' => 'u2',
            'iptv_password' => 'passw',
        ]);

        $this->withHeaders($this->englishApi())
            ->postJson('/api/v1/device/session', [
                'device_id' => 'device-no-subscription-001',
                'ip_address' => '10.0.0.2',
            ])
            ->assertStatus(403)
            ->assertJsonPath('subscription_status', 'none');
    }

    public function test_activating_subscription_auto_approves_device_when_credentials_exist(): void
    {
        $device = Device::query()->create([
            'device_id' => 'device-sub-activation-001',
            'registered_ip' => '10.0.0.3',
            'status' => DeviceStatus::Pending,
            'iptv_username' => 'u3',
            'iptv_password' => 'passw',
        ]);

        $device->activateSubscription(plan: SubscriptionPlan::Premium);
        $device->refresh();

        $this->assertSame(DeviceStatus::Approved, $device->status);
        $this->assertTrue($device->isApiReady());
    }

    public function test_requires_app_key_when_enabled(): void
    {
        config(['iptv.require_app_key' => true]);
        config(['iptv.device_app_key' => 'secret-key']);

        $this->withHeaders($this->englishApi())
            ->postJson('/api/v1/device/register', [
                'device_id' => 'device-no-key-001',
                'ip_address' => '127.0.0.1',
            ])->assertStatus(401);

        $this->withHeaders(array_merge($this->englishApi(), ['X-App-Key' => 'secret-key']))
            ->postJson('/api/v1/device/register', [
                'device_id' => 'device-with-key-001',
                'ip_address' => '127.0.0.1',
            ])->assertOk();
    }
}
