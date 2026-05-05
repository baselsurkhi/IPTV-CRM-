<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        /** @var \App\Models\Device $device */
        $device = $request->user();   // Sanctum token resolves to Device model

        return response()->json([
            'subscribed'       => $device->isSubscribed(),
            'status'           => $device->subscriptionStatus(),
            'plan'             => $device->subscription_plan?->value,
            'plan_label'       => $device->subscription_plan?->label(),
            'expires_at'       => $device->expires_at?->toIso8601String(),
            'days_remaining'   => $device->daysRemaining(),
            'locale'           => app()->getLocale(),
        ]);
    }
}