<?php

namespace App\Filament\Widgets;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeviceStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Device::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('control.online'), Device::query()->where('isdeleted', false)->where('last_seen_at', '>', now()->subSeconds(90))->whereHas('tokens')->count())->color('success')->description(__('control.recently_online')),
            Stat::make(__('control.all'), Device::query()->where('isdeleted', false)->count())->color('info'),
            Stat::make(__('admin.stats_pending'), Device::query()->where('isdeleted', false)->where('status', DeviceStatus::Pending)->count())
                ->description(__('admin.stats_pending_hint'))
                ->color('warning'),
            Stat::make(__('admin.stats_approved'), Device::query()->where('isdeleted', false)->where('status', DeviceStatus::Approved)->count())
                ->description(__('admin.stats_approved_hint'))
                ->color('success'),
            Stat::make(__('admin.stats_blocked'), Device::query()->where('isdeleted', false)->whereIn('status', [DeviceStatus::Blocked, DeviceStatus::Rejected])->count())
                ->description(__('admin.stats_blocked_hint'))
                ->color('danger'),
        ];
    }
}
