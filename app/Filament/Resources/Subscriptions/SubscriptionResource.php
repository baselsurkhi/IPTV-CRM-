<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\Tables\SubscriptionsTable;
use App\Models\Device;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $slug = 'device-subscriptions';

    protected static ?int $navigationSort = 25;

    protected static ?string $model = Device::class;

    protected static ?string $recordTitleAttribute = 'device_id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.group_devices');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.subscriptions');
    }

    public static function getModelLabel(): string
    {
        return __('admin.subscription_model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.navigation.subscriptions');
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Device::class);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
