<?php

namespace App\Filament\Resources\Devices\Pages;

use App\Filament\Resources\Devices\DeviceResource;
use App\Models\Device;
use App\Services\SaveDeviceSubscription;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDevice extends CreateRecord
{
    public function getTitle(): string
    {
        return __('workflow.add');
    }

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveDeviceSubscription::class)->save(new Device, $data);
    }

    protected function getRedirectUrl(): string
    {
        return DeviceResource::getUrl('index');
    }

    protected static string $resource = DeviceResource::class;
}
