<?php

namespace App\Filament\Widgets;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeviceStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('admin.stats_pending'), Device::query()->where('status', DeviceStatus::Pending)->count())
                ->description(__('admin.stats_pending_hint'))
                ->color('warning'),
            Stat::make(__('admin.stats_approved'), Device::query()->where('status', DeviceStatus::Approved)->count())
                ->description(__('admin.stats_approved_hint'))
                ->color('success'),
            Stat::make(__('admin.stats_blocked'), Device::query()->whereIn('status', [DeviceStatus::Blocked, DeviceStatus::Rejected])->count())
                ->description(__('admin.stats_blocked_hint'))
                ->color('danger'),
        ];
    }
}
