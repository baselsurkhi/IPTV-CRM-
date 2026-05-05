<?php

namespace App\Filament\Resources\Devices;

use App\Filament\Resources\Devices\Pages\CreateDevice;
use App\Filament\Resources\Devices\Pages\EditDevice;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Filament\Resources\Devices\Schemas\DeviceForm;
use App\Filament\Resources\Devices\Tables\DevicesTable;
use App\Authorization\PermissionsRegistry;
use App\Models\Device;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $slug = 'device-registry';

    protected static ?int $navigationSort = 20;

    protected static ?string $model = Device::class;

    protected static ?string $recordTitleAttribute = 'device_id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.group_devices');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.devices');
    }

    public static function getModelLabel(): string
    {
        return __('admin.devices_model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.navigation.devices');
    }

    public static function form(Schema $schema): Schema
    {
        return DeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DevicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
            'create' => CreateDevice::route('/create'),
            'edit' => EditDevice::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Device::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Device::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        $u = auth()->user();

        return $u !== null && $u->can(PermissionsRegistry::DEVICES_DELETE);
    }
}
