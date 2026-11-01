<?php

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Device;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDeviceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $examples = [
                ['active', 'أحمد — تجريبي', DeviceStatus::Approved, 90, SubscriptionPlan::Standard, false],
                ['premium', 'ليان — تجريبي', DeviceStatus::Approved, 180, SubscriptionPlan::Premium, false],
                ['vip', 'عمر — تجريبي', DeviceStatus::Approved, 365, SubscriptionPlan::Vip, false],
                ['expiring', 'نور — تجريبي', DeviceStatus::Approved, 3, SubscriptionPlan::Trial, false],
                ['expired', 'سارة — تجريبي', DeviceStatus::Approved, -10, SubscriptionPlan::Standard, false],
                ['pending', 'يوسف — تجريبي', DeviceStatus::Pending, null, null, false],
                ['blocked', 'ريم — تجريبي', DeviceStatus::Blocked, 60, SubscriptionPlan::Standard, false],
                ['rejected', 'كريم — تجريبي', DeviceStatus::Rejected, null, null, false],
                ['deleted', 'هبة — تجريبي', DeviceStatus::Approved, 60, SubscriptionPlan::Standard, true],
            ];
            foreach ($examples as [$key, $name, $status, $days, $plan, $deleted]) {
                $device = Device::firstOrCreate(['device_id' => 'demo-crm-'.$key], [
                    'subscriber_name' => $name, 'device_name' => 'Demo TV — '.$key,
                    'registered_ip' => '192.0.2.10', 'status' => $status, 'isdeleted' => $deleted,
                    'iptv_username' => $days !== null ? 'demo_'.$key : null,
                    'iptv_password' => $days !== null ? Str::random(32) : null,
                    'player_api_base_url' => 'https://example.invalid/player_api.php',
                    'subscription_plan' => $plan ?? SubscriptionPlan::Standard,
                    'subscribed_at' => $days !== null ? now()->subMonths(12) : null,
                    'expires_at' => $days !== null ? now()->addDays($days) : null,
                    'subscription_months' => $days !== null ? 12 : 0,
                    'approved_at' => $status === DeviceStatus::Approved ? now()->subDay() : null,
                    'blocked_at' => $status === DeviceStatus::Blocked ? now() : null,
                    'rejected_at' => $status === DeviceStatus::Rejected ? now() : null,
                    'admin_note' => 'بيانات تجريبية للعرض فقط — لا تتصل بمزود IPTV حقيقي.',
                ]);
                if (! $device->wasRecentlyCreated) {
                    continue;
                }
                $device->forceFill(['platform' => 'android', 'app_version' => 'demo',
                    'last_seen_at' => now()->subHours(2), 'device_metadata' => ['demo' => true]])->save();
                $device->recordEvent('demo_created');
                if ($days !== null) {
                    $device->renewals()->create([
                        'plan' => $plan->value, 'months' => 12, 'new_expires_at' => $device->expires_at,
                        'amount' => 0, 'currency' => 'USD', 'renewed_by' => 'demo-seeder',
                        'notes' => 'Demo subscription — no payment collected',
                    ]);
                }
            }
        });
    }
}
