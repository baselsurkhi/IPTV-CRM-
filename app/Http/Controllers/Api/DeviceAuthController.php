<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeviceStatus;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:191'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:45'],
        ]);

        if (! filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            return response()->json(['message' => __('api.invalid_ip')], 422);
        }

        /** @var Device $device */
        $device = Device::query()->firstOrNew(['device_id' => $data['device_id']]);

        if ($device->exists && $device->status === DeviceStatus::Blocked) {
            return response()->json([
                'message' => __('api.blocked'),
                'status' => $device->status->value,
            ], 403);
        }

        $device->registered_ip = $data['ip_address'];
        $device->last_seen_ip = $data['ip_address'];
        $this->syncDeviceName($device, $data);

        if (! $device->exists) {
            $device->status = DeviceStatus::Pending;
        }

        $device->save();

        return response()->json([
            'message' => match ($device->status) {
                DeviceStatus::Pending => __('api.awaiting_approval'),
                DeviceStatus::Approved => __('api.already_approved'),
                DeviceStatus::Rejected => __('api.rejected'),
                DeviceStatus::Blocked => __('api.blocked'),
            },
            'status' => $device->status->value,
            'locale' => app()->getLocale(),
            'registered' => true,
        ]);
    }

    public function session(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:191'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:45'],
        ]);

        if (! filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            return response()->json(['message' => __('api.invalid_ip')], 422);
        }

        $device = Device::query()->where('device_id', $data['device_id'])->first();

        if ($device === null) {
            return response()->json(['message' => __('api.unknown_device')], 404);
        }

        if ($device->locked_until && $device->locked_until->isFuture()) {
            return response()->json([
                'message' => __('api.locked'),
                'locked_until' => $device->locked_until->toIso8601String(),
            ], 429);
        }

        if (config('iptv.strict_ip_login')) {
            $expectedIp = $device->last_seen_ip ?? $device->registered_ip;
            if ($expectedIp !== $data['ip_address']) {
                return response()->json([
                    'message' => __('api.ip_mismatch'),
                ], 403);
            }
        }

        $device->last_seen_ip = $data['ip_address'];
        $this->syncDeviceName($device, $data);
        $device->save();

        if ($device->status === DeviceStatus::Blocked) {
            return response()->json([
                'message' => __('api.blocked'),
                'status' => $device->status->value,
                'locale' => app()->getLocale(),
            ], 403);
        }

        if ($device->status === DeviceStatus::Pending) {
            return response()->json([
                'message' => __('api.awaiting_approval'),
                'status' => $device->status->value,
                'locale' => app()->getLocale(),
            ], 403);
        }

        if ($device->status === DeviceStatus::Rejected) {
            return response()->json([
                'message' => __('api.rejected'),
                'status' => $device->status->value,
                'locale' => app()->getLocale(),
            ], 403);
        }

        if ($device->status !== DeviceStatus::Approved) {
            return response()->json(['message' => __('api.invalid_state')], 500);
        }

        if ($device->expires_at === null) {
            return response()->json([
                'message' => __('api.subscription_required'),
                'status' => $device->status->value,
                'subscription_status' => 'none',
                'locale' => app()->getLocale(),
            ], 403);
        }

        if (! $device->isSubscribed()) {
            return response()->json([
                'message' => __('api.subscription_expired'),
                'status' => $device->status->value,
                'subscription_status' => $device->subscriptionStatus(),
                'expires_at' => $device->expires_at?->toIso8601String(),
                'locale' => app()->getLocale(),
            ], 403);
        }

        $username = $device->iptv_username;
        $playerApiUrl = $device->playerApiUrl();

        if ($playerApiUrl === null) {
            $this->bumpFails($device);

            return response()->json([
                'message' => __('api.credentials_missing'),
            ], 503);
        }

        if (config('iptv.single_active_token', true)) {
            $device->revokeAllTokens();
        }

        $token = $device->createToken('device')->plainTextToken;

        $device->failed_login_attempts = 0;
        $device->locked_until = null;
        $device->last_login_at = now();
        $device->save();

        return response()->json([
            'message' => __('api.ok'),
            'status' => $device->status->value,
            'locale' => app()->getLocale(),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'player_api_url' => $playerApiUrl,
            'username' => $username,
            'password' => $device->iptv_password,
            'subscription_status' => $device->subscriptionStatus(),
            'expires_at' => $device->expires_at?->toIso8601String(),
            'days_remaining' => $device->daysRemaining(),
        ]);
    }

    private function bumpFails(Device $device): void
    {
        $device->failed_login_attempts = min(255, $device->failed_login_attempts + 1);
        $max = config('iptv.failed_login_before_lockout', 8);
        if ($device->failed_login_attempts >= $max) {
            $device->locked_until = now()->addMinutes((int) config('iptv.lockout_duration_minutes', 30));
        }
        $device->save();
    }

    private function syncDeviceName(Device $device, array $data): void
    {
        if (! array_key_exists('device_name', $data)) {
            return;
        }

        $device->device_name = filled($data['device_name'])
            ? trim((string) $data['device_name'])
            : null;
    }
}
