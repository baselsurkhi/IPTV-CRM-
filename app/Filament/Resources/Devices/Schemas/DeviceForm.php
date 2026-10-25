<?php

namespace App\Filament\Resources\Devices\Schemas;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Carbon\Carbon;
use Filament\Forms\Components\KeyValue;
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
                Section::make(__('control.telemetry'))->description(__('control.telemetry_help'))->columns(['md' => 2, 'xl' => 3])->columnSpanFull()->visibleOn('edit')->schema([
                    ...collect(['login_method', 'platform', 'app_version', 'public_ip', 'last_seen_at', 'last_login_at'])->map(fn ($field) => TextInput::make($field)->label(__('control.'.$field))->formatStateUsing(fn ($state) => blank($state) ? '—' : (in_array($field, ['last_seen_at', 'last_login_at']) ? Carbon::parse($state)->format('Y-m-d H:i') : ($field === 'login_method' ? __('control.'.$state) : $state)))->disabled()->dehydrated(false))->all(),
                    KeyValue::make('device_metadata')->label(__('control.telemetry'))->disabled()->dehydrated(false)->columnSpanFull(),
                ]),
                Section::make(__('admin.device_network_section'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('subscriber_name')->label(__('control.subscriber'))->maxLength(255),
                        TextInput::make('device_id')
                            ->label(__('studio.device_id'))
                            ->required()
                            ->maxLength(191)
                            ->unique(table: Device::class, column: 'device_id', ignoreRecord: true)
                            ->helperText(__('studio.device_id_hint')),
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
                    ->visible(fn (?Device $record): bool => ! $record || $record->login_method === 'activation')
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
