<?php

namespace Tests\Feature;

use App\Enums\DeviceStatus;
use App\Filament\Resources\Devices\Pages\CreateDevice;
use App\Filament\Resources\Devices\Pages\EditDevice;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Models\Device;
use App\Models\User;
use Database\Seeders\DemoDeviceSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Gate::before(fn () => true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_create_saves_user_subscription_and_approval_together(): void
    {
        Livewire::test(CreateDevice::class)->fillForm([
            'device_id' => 'one-step', 'registered_ip' => '192.168.1.2',
            'subscriber_name' => 'Subscriber', 'iptv_username' => 'demo', 'iptv_password' => 'secret',
            'subscription_action' => 'activate', 'months' => 12, 'plan' => 'standard',
        ])->call('create')->assertHasNoFormErrors();
        $device = Device::where('device_id', 'one-step')->firstOrFail();
        $this->assertTrue($device->isApiReady());
        $this->assertSame(1, $device->renewals()->count());
        $this->assertSame('secret', $device->iptv_password);

        $expiry = $device->expires_at->toDateTimeString();
        Livewire::test(EditDevice::class, ['record' => $device->getKey()])
            ->fillForm(['subscriber_name' => 'Updated', 'subscription_action' => 'none'])
            ->call('save')->assertHasNoFormErrors();
        $device->refresh();
        $this->assertSame($expiry, $device->expires_at->toDateTimeString());
        $this->assertSame(1, $device->renewals()->count());
        $this->assertSame('secret', $device->iptv_password);
    }

    public function test_deleted_records_can_be_found_and_restored_without_removing_existing_bans(): void
    {
        $device = Device::create(['device_id' => 'deleted', 'registered_ip' => '127.0.0.1',
            'status' => DeviceStatus::Blocked, 'isdeleted' => true]);
        Livewire::test(ListDevices::class)->assertCanNotSeeTableRecords([$device])
            ->set('activeTab', 'deleted')->assertCanSeeTableRecords([$device])
            ->callTableAction('restore', $device);
        $this->assertFalse($device->refresh()->isdeleted);
        $this->assertSame(DeviceStatus::Blocked, $device->status);
        Livewire::test(ListDevices::class)->set('activeTab', 'blocked')->assertCanSeeTableRecords([$device])
            ->callTableAction('unblock', $device);
        $this->assertSame(DeviceStatus::Pending, $device->refresh()->status);
    }

    public function test_deletion_revokes_sessions_and_restore_preserves_subscription(): void
    {
        $device = Device::create(['device_id' => 'restore', 'registered_ip' => '127.0.0.1',
            'status' => DeviceStatus::Approved, 'iptv_username' => 'demo', 'iptv_password' => 'secret',
            'expires_at' => now()->addMonth()]);
        $device->createToken('test');
        $linked = Device::create(['device_id' => 'managed-restore', 'registered_ip' => '127.0.0.1', 'status' => DeviceStatus::Approved]);
        $linked->createToken('linked-test');
        Livewire::test(ListDevices::class)->callTableAction('softDelete', $device);
        $this->assertTrue($device->refresh()->isdeleted);
        $this->assertSame(0, $device->tokens()->count());
        $this->assertSame(0, $linked->tokens()->count());
        $this->assertSame(DeviceStatus::Rejected, $device->status);
        $this->assertNotNull($device->rejected_at);
        Livewire::test(ListDevices::class)->assertCanNotSeeTableRecords([$device])->set('activeTab', 'deleted')->assertCanSeeTableRecords([$device]);
        $this->assertSame('device_deleted', $device->installationDenialCode());
        Livewire::test(ListDevices::class)->set('activeTab', 'deleted')->callTableAction('restore', $device);
        $this->assertSame('device_rejected', $device->refresh()->installationDenialCode());
        $this->assertTrue($device->isSubscribed());
        $this->assertTrue($device->hasPlayerCredentials());
    }

    public function test_incomplete_subscription_is_not_saved(): void
    {
        Livewire::test(CreateDevice::class)->fillForm([
            'device_id' => 'invalid', 'registered_ip' => '192.168.1.2',
            'subscription_action' => 'activate', 'months' => 0, 'plan' => 'standard',
        ])->call('create')->assertHasFormErrors(['months', 'iptv_username', 'iptv_password']);
        $this->assertDatabaseMissing('devices', ['device_id' => 'invalid']);
    }

    public function test_unblock_restores_ready_subscriber_without_reentering_credentials(): void
    {
        $device = Device::create(['device_id' => 'blocked', 'registered_ip' => '127.0.0.1',
            'status' => DeviceStatus::Blocked, 'iptv_username' => 'demo', 'iptv_password' => 'secret',
            'expires_at' => now()->addMonth(), 'blocked_at' => now()]);
        Livewire::test(ListDevices::class)->set('activeTab', 'blocked')->callTableAction('unblock', $device);
        $this->assertTrue($device->refresh()->isApiReady());
        $this->assertNull($device->blocked_at);
    }

    public function test_renewal_extends_existing_expiry_once(): void
    {
        $device = Device::create(['device_id' => 'renew', 'registered_ip' => '127.0.0.1',
            'status' => DeviceStatus::Approved, 'iptv_username' => 'demo', 'iptv_password' => 'secret',
            'expires_at' => now()->addMonth(), 'subscription_plan' => 'standard']);
        $expected = $device->expires_at->copy()->addMonths(3)->toDateTimeString();
        Livewire::test(ListDevices::class)->callTableAction('subscription', $device, [
            'months' => 3, 'plan' => 'standard',
        ])->assertHasNoActionErrors();
        $this->assertSame($expected, $device->refresh()->expires_at->toDateTimeString());
        $this->assertSame(1, $device->renewals()->count());
    }

    public function test_demo_seed_is_repeatable_and_unified_page_filters_subscriptions(): void
    {
        $this->seed(DemoDeviceSeeder::class);
        $this->seed(DemoDeviceSeeder::class);
        $this->assertSame(9, Device::where('device_id', 'like', 'demo-crm-%')->count());
        $this->assertDatabaseCount('subscription_renewals', 7);
        $expired = Device::where('device_id', 'demo-crm-expired')->firstOrFail();
        $active = Device::where('device_id', 'demo-crm-active')->firstOrFail();
        Livewire::test(ListDevices::class)->filterTable('subscription_status', 'expired')
            ->assertCanSeeTableRecords([$expired])->assertCanNotSeeTableRecords([$active]);
        Livewire::test(ListDevices::class)->callTableAction('cancelSubscription', $active, ['notes' => 'Test cancellation']);
        $this->assertFalse($active->refresh()->isSubscribed());
        $this->get('/admin/device-subscriptions')->assertRedirect('/admin/device-registry');
        $this->get('/admin/subscriptions')->assertRedirect('/admin/device-registry');
    }

    public function test_quick_activation_collects_only_missing_credentials(): void
    {
        $device = Device::create(['device_id' => 'quick', 'registered_ip' => '192.0.2.1', 'status' => DeviceStatus::Pending]);
        Livewire::test(ListDevices::class)->callTableAction('subscription', $device, [
            'months' => 6, 'plan' => 'standard', 'iptv_username' => 'quick-user', 'iptv_password' => 'quick-secret',
        ])->assertHasNoActionErrors();
        $this->assertTrue($device->refresh()->isApiReady());
        $this->assertSame('192.0.2.1', $device->registered_ip);
        $this->assertSame(1, $device->renewals()->count());
        $expiry = $device->expires_at->copy()->addMonths(3)->toDateTimeString();
        Livewire::test(ListDevices::class)->callTableAction('subscription', $device, [
            'months' => 3, 'plan' => 'premium',
        ])->assertHasNoActionErrors();
        $this->assertSame($expiry, $device->refresh()->expires_at->toDateTimeString());
        $this->assertSame('quick-secret', $device->iptv_password);
        $this->assertSame(2, $device->renewals()->count());
    }

    public function test_editing_details_cannot_change_display_only_identity_or_expiry(): void
    {
        $device = Device::create(['device_id' => 'immutable', 'registered_ip' => '192.0.2.1',
            'status' => DeviceStatus::Approved, 'expires_at' => now()->addMonth()]);
        $expiry = $device->expires_at->toDateTimeString();
        Livewire::test(EditDevice::class, ['record' => $device->getKey()])->fillForm([
            'subscriber_name' => 'New name', 'device_id' => 'tampered', 'registered_ip' => '192.0.2.99',
            'expires_at' => now()->addYears(10)->toDateTimeString(),
        ])->call('save')->assertHasNoFormErrors();
        $device->refresh();
        $this->assertSame('immutable', $device->device_id);
        $this->assertSame('192.0.2.1', $device->registered_ip);
        $this->assertSame($expiry, $device->expires_at->toDateTimeString());
        $this->assertSame('New name', $device->subscriber_name);
    }

    public function test_bulk_and_edit_page_deletion_reject_and_revoke(): void
    {
        $first = Device::create(['device_id' => 'bulk-delete', 'registered_ip' => '192.0.2.1', 'status' => DeviceStatus::Approved]);
        $second = Device::create(['device_id' => 'edit-delete', 'registered_ip' => '192.0.2.2', 'status' => DeviceStatus::Approved]);
        $first->createToken('first');
        $second->createToken('second');
        Livewire::test(ListDevices::class)->callTableBulkAction('softDelete', [$first]);
        Livewire::test(EditDevice::class, ['record' => $second->getKey()])->callAction('softDelete');
        foreach ([$first, $second] as $device) {
            $this->assertTrue($device->refresh()->isdeleted);
            $this->assertSame(DeviceStatus::Rejected, $device->status);
            $this->assertSame(0, $device->tokens()->count());
        }
    }

    public function test_legacy_deleted_repair_revokes_linked_sessions_and_preserves_data(): void
    {
        $deleted = Device::create(['device_id' => 'legacy', 'registered_ip' => '192.0.2.1',
            'isdeleted' => true, 'status' => DeviceStatus::Approved,
            'expires_at' => now()->addMonth(), 'iptv_username' => 'legacy', 'iptv_password' => 'secret']);
        $linked = Device::create(['device_id' => 'managed-legacy', 'registered_ip' => '192.0.2.1', 'status' => DeviceStatus::Approved]);
        $untouched = Device::create(['device_id' => 'unrelated', 'registered_ip' => '192.0.2.2', 'status' => DeviceStatus::Approved]);
        foreach ([$deleted, $linked, $untouched] as $device) {
            $device->createToken('existing');
        }
        $this->assertFalse($deleted->isApiReady());
        $this->assertSame('blocked', $deleted->apiAccessState());
        $this->artisan('devices:repair-deleted')->assertSuccessful();
        $this->assertSame(DeviceStatus::Approved, $deleted->fresh()->status);
        $this->artisan('devices:repair-deleted', ['--apply' => true])->assertSuccessful();
        $this->artisan('devices:repair-deleted', ['--apply' => true])->assertSuccessful();
        $this->assertSame(DeviceStatus::Rejected, $deleted->refresh()->status);
        $this->assertTrue($deleted->isdeleted);
        $this->assertTrue($deleted->isSubscribed());
        $this->assertSame('secret', $deleted->iptv_password);
        $this->assertSame(0, $deleted->tokens()->count());
        $this->assertSame(0, $linked->tokens()->count());
        $this->assertSame(1, $untouched->tokens()->count());
        $this->assertSame(DeviceStatus::Approved, $untouched->fresh()->status);
    }
}
