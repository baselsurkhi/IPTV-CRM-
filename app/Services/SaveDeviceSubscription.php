<?php

namespace App\Services;

use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Device;
use Illuminate\Support\Facades\DB;

class SaveDeviceSubscription
{
    public function save(Device $device, array $data): Device
    {
        return DB::transaction(function () use ($device, $data): Device {
            $mode = $data['subscription_action'] ?? 'none';
            $months = (int) ($data['months'] ?? 12);
            $plan = SubscriptionPlan::from($data['plan'] ?? SubscriptionPlan::Standard->value);
            $amount = filled($data['amount'] ?? null) ? (float) $data['amount'] : null;
            $reference = $data['payment_ref'] ?? null;
            $notes = $data['notes'] ?? null;
            unset($data['subscription_action'], $data['months'], $data['plan'], $data['amount'], $data['payment_ref'], $data['notes']);
            $device->fill($data)->save();
            if ($mode !== 'none' && ! $device->isdeleted) {
                $method = $mode === 'renew' ? 'renewSubscription' : 'activateSubscription';
                $device->$method(months: $months, plan: $plan, notes: $notes,
                    renewedBy: auth()->user()?->email, amount: $amount, paymentRef: $reference);
            }
            if ($device->isSubscribed() && $device->status === DeviceStatus::Pending) {
                $device->approveForApi(auth()->id());
            }

            return $device;
        });
    }
}
