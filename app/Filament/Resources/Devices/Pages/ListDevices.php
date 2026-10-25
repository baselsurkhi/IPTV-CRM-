<?php

namespace App\Filament\Resources\Devices\Pages;

use App\Filament\Resources\Devices\DeviceResource;
use App\Models\Device;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    public function getTabs(): array
    {
        $base = Device::query()->where('isdeleted', false);

        return [
            'all' => Tab::make(__('studio.all'))->badge((clone $base)->count()),
            'pending' => Tab::make(__('studio.pending'))->badge((clone $base)->where('status', 'pending')->count())->modifyQueryUsing(fn ($query) => $query->where('status', 'pending')),
            'approved' => Tab::make(__('admin.stats_approved'))->modifyQueryUsing(fn ($query) => $query->where('status', 'approved')),
            'blocked' => Tab::make(__('admin.stats_blocked'))->modifyQueryUsing(fn ($query) => $query->whereIn('status', ['blocked', 'rejected'])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
