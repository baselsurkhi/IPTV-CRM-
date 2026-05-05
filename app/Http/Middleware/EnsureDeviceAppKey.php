<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('iptv.require_app_key', false)) {
            return $next($request);
        }

        $expected = config('iptv.device_app_key');
        if (! is_string($expected) || $expected === '') {
            abort(503, 'DEVICE_APP_KEY is not configured.');
        }

        $header = $request->header('X-App-Key', '');
        if (! hash_equals($expected, $header)) {
            return response()->json(['message' => __('api.unauthorized')], 401);
        }

        return $next($request);
    }
}
