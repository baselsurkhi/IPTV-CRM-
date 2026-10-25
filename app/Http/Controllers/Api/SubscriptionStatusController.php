<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeviceStatus;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionStatusController extends Controller
{
    /**
     * Called by the IPTV device after authenticating with a Bearer token.
     * Returns subscription status so the app can warn the user.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();
        abort_unless($device instanceof Device, 403);
        abort_if($device->installationDenied() || $device->status !== DeviceStatus::Approved, 403);
        $device->forceFill(['last_seen_at' => now(), 'public_ip' => $request->ip()])->save();
        if ($device->login_method !== 'activation') {
            return response()->json(['subscribed' => true, 'status' => 'active']);
        }   // Sanctum token resolves to Device model

        return response()->json([
            'subscribed' => $device->isSubscribed(),
            'status' => $device->subscriptionStatus(),
            'plan' => $device->subscription_plan?->value,
            'plan_label' => $device->subscription_plan?->label(),
            'expires_at' => $device->expires_at?->toIso8601String(),
            'days_remaining' => $device->daysRemaining(),
            'locale' => app()->getLocale(),
        ]);
    }
}
