<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\Device|null $device */
        $device = $request->user();

        if (! $device) {
            return response()->json(['message' => __('api.unauthenticated')], 401);
        }

        // If device has never been given a subscription, pass through
        // (you can make this strict by returning 403 instead)
        if ($device->expires_at === null) {
            return $next($request);
        }

        if ($device->isExpired()) {
            return response()->json([
                'message'    => __('api.subscription_expired'),
                'status'     => 'expired',
                'expires_at' => $device->expires_at->toIso8601String(),
            ], 403);
        }

        return $next($request);
    }
}