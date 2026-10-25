<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class OperationsOverview extends Widget
{
    protected string $view = 'admin.operations-overview';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Device::class) ?? false;
    }

    protected function getViewData(): array
    {
        abort_unless(static::canView(), 403);

        $devices = Device::query()->where('isdeleted', false);
        $counts = (clone $devices)->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked")->first();
        $online = (clone $devices)->where('last_seen_at', '>', now()->subSeconds(90))->whereHas('tokens')->count();
        $expiring = (clone $devices)->where('login_method', 'activation')->whereBetween('expires_at', [now(), now()->addDays(7)])->count();
        $methods = (clone $devices)->selectRaw('login_method, COUNT(*) as total')->groupBy('login_method')->pluck('total', 'login_method');
        $recent = (clone $devices)->orderByDesc('last_seen_at')->limit(5)->get();
        $attention = (clone $devices)->where('status', 'pending')->oldest()->limit(4)->get();
        $events = DB::table('device_events')->join('devices', 'devices.id', '=', 'device_events.device_id')->where('devices.isdeleted', false)->select('device_events.event', 'device_events.created_at', 'devices.device_name')->orderByDesc('device_events.id')->limit(5)->get();

        return compact('counts', 'online', 'expiring', 'methods', 'recent', 'attention', 'events');
    }
}
