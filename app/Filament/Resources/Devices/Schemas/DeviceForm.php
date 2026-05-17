<?php

namespace App\Filament\Resources\Devices\Schemas;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.device_network_section'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('device_id')
                            ->label('Device ID')
                            ->required()
                            ->maxLength(191)
                            ->unique(table: Device::class, column: 'device_id', ignoreRecord: true)
                            ->helperText('Unique device identifier'),
                        TextInput::make('device_name')
                            ->label(__('admin.device_name'))
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('admin.approval_status'))
                            ->options(
                                collect(DeviceStatus::cases())->mapWithKeys(fn (DeviceStatus $s): array => [
                                    $s->value => $s->label(),
                                ])->all()
                            )
                            ->native(false)
                            ->required(),
                        TextInput::make('registered_ip')
                            ->label(__('admin.registered_ip'))
                            ->required()
                            ->rules(['ip']),
                        TextInput::make('last_seen_ip')
                            ->label(__('admin.last_ip'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ]),
                Section::make(__('admin.player_subscription_section'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('iptv_username')
                            ->label(__('admin.iptv_username')),
                        TextInput::make('iptv_password')
                            ->label(__('admin.players_password'))
                            ->password()
                            ->revealable(),
                        TextInput::make('player_api_base_url')
                            ->label(__('admin.default_player_api_url'))
                            ->url()
                            ->maxLength(255)
                            ->placeholder(config('iptv.player_api_base_url'))
                            ->helperText(__('admin.player_url_optional')),
                    ]),
                Section::make(__('admin.internal_notes'))
                    ->schema([
                        Textarea::make('admin_note')
                            ->label(__('admin.staff_notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
