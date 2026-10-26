<?php

namespace App\Console\Commands;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreDeviceAccess extends Command
{
    protected $signature = 'devices:restore-access {device_id : Installation UUID or managed device ID} {--apply : Apply the restoration; otherwise only show current state}';

    protected $description = 'Restore deleted, blocked or rejected records for one installation without changing its credentials or subscription';

    public function handle(): int
    {
        $id = (string) $this->argument('device_id');
        $id = str_starts_with($id, 'managed-') ? substr($id, 8) : $id;

        return DB::transaction(function () use ($id) {
            $devices = Device::query()->whereIn('device_id', [$id, 'managed-'.$id])->lockForUpdate()->get();
            if ($devices->isEmpty()) {
                $this->error('No records found for this installation.');

                return self::FAILURE;
            }

            $this->table(['Device ID', 'Status', 'Deleted'], $devices->map(fn (Device $device) => [
                $device->device_id, $device->status->value, $device->isdeleted ? 'yes' : 'no',
            ])->all());

            if (! $this->option('apply')) {
                $this->info('Preview only. Add --apply to restore access for these records.');

                return self::SUCCESS;
            }

            foreach ($devices as $device) {
                if (! $device->isdeleted && ! in_array($device->status, [DeviceStatus::Blocked, DeviceStatus::Rejected], true)) {
                    continue;
                }
                $device->isdeleted = false;
                if (in_array($device->status, [DeviceStatus::Blocked, DeviceStatus::Rejected], true)) {
                    $device->status = $device->login_method === 'activation' ? DeviceStatus::Pending : DeviceStatus::Approved;
                }
                $device->blocked_at = null;
                $device->rejected_at = null;
                $device->save();
                $device->recordEvent('access_restored:console');
            }
            $this->info('Access restrictions removed. Retry login in the app. Activation records still require approval and an active subscription.');

            return self::SUCCESS;
        });
    }
}
