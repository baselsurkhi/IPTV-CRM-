<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeviceStatus;
use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagedSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'uuid'],
            'installation_secret' => ['required', 'string', 'min:32', 'max:128'],
            'login_method' => ['required', 'in:xtream,m3u'],
            'm3u_source_url' => ['nullable', 'string', 'max:8192', 'url:http,https'],
            'xtream_server_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'device_name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'max:40'],
            'app_version' => ['required', 'string', 'max:80'],
            'local_ip' => ['nullable', 'ip'],
            'metadata' => ['sometimes', 'array:manufacturer,model,release,sdkInt,brand,device,buildId,isTelevision,os_version,locale'],
            'metadata.*' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            // Separate namespace avoids changing activation credentials or subscription entitlements.
            $id = 'managed-'.$data['device_id'];
            $device = Device::query()->firstOrCreate(['device_id' => $id], [
                'status' => DeviceStatus::Approved,
                'registered_ip' => $request->ip(),
                'installation_secret_hash' => hash('sha256', $data['installation_secret']),
            ]);
            $device = Device::query()->lockForUpdate()->findOrFail($device->id);
            abort_unless(hash_equals($device->installation_secret_hash ?? '', hash('sha256', $data['installation_secret'])), 403);
            abort_if($device->installationDenied(), 403);
            $device->forceFill([
                'login_method' => $data['login_method'],
                'm3u_source_url' => $data['login_method'] === 'm3u' ? ($data['m3u_source_url'] ?? null) : null,
                'xtream_server_url' => $data['login_method'] === 'xtream'
                    ? $this->serverUrl($data['xtream_server_url'] ?? null) : null,
                'device_name' => $data['device_name'],
                'platform' => $data['platform'],
                'app_version' => $data['app_version'],
                'device_metadata' => $data['metadata'] ?? [],
                'public_ip' => $request->ip(),
                'last_seen_ip' => $data['local_ip'] ?? $request->ip(),
                'last_seen_at' => now(),
                'last_login_at' => now(),
            ])->save();
            $device->revokeAllTokens();
            $device->recordEvent('login:'.$data['login_method']);
            $token = $device->createToken('managed-player', ['player:heartbeat'])->plainTextToken;

            return response()->json(['access_token' => $token, 'status' => 'approved']);
        });
    }

    private function serverUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        // Store only the server address, never credentials or query tokens.
        $parts = parse_url($url);

        return strtolower($parts['scheme']).'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '');
    }

    public function destroy(Request $request): JsonResponse
    {
        abort_unless($request->user() instanceof Device, 403);
        $request->user()->recordEvent('logout');
        $request->user()->currentAccessToken()->delete();

        return response()->json(['logged_out' => true]);
    }
}
