<?php

namespace App\Filament\Resources\Devices\Schemas;

use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Device;
use App\Models\SiteSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('workflow.current'))->description(__('workflow.read_only'))->columnSpanFull()->columns(3)->visibleOn('edit')->schema([
                    TextEntry::make('device_id')->label(__('studio.device_id')),
                    TextEntry::make('status')->label(__('admin.status'))->formatStateUsing(fn (DeviceStatus $state) => $state->label())->badge(),
                    TextEntry::make('expires_at')->label(__('admin.subscription_expires'))->dateTime('Y-m-d H:i')->placeholder(__('admin.subscription_none')),
                    TextEntry::make('registered_ip')->label(__('admin.registered_ip')),
                    TextEntry::make('last_seen_ip')->label(__('admin.last_ip'))->placeholder('—'),
                ]),
                Section::make(__('control.telemetry'))->description(__('workflow.read_only'))->columnSpanFull()->visibleOn('edit')->collapsible()->collapsed()->columns(3)->schema([
                    ...collect(['login_method', 'platform', 'app_version', 'public_ip', 'last_seen_at', 'last_login_at'])->map(fn ($field) => TextEntry::make($field)->label(__('control.'.$field))->placeholder('—'))->all(),
                    TextEntry::make('xtream_server_url')->label(__('control.xtream_server_url'))->columnSpanFull()->visible(fn (?Device $record): bool => $record?->login_method === 'xtream'),
                    TextEntry::make('m3u_source_url')->label(__('control.m3u_source_url'))->columnSpanFull()->visible(fn (?Device $record): bool => $record?->login_method === 'm3u'),
                ]),
                Section::make(__('workflow.editable'))->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('subscriber_name')->label(__('control.subscriber'))->maxLength(255),
                        TextInput::make('device_id')->visibleOn('create')
                            ->label(__('studio.device_id'))
                            ->required()
                            ->maxLength(191)
                            ->unique(table: Device::class, column: 'device_id', ignoreRecord: true)
                            ->helperText(__('studio.device_id_hint')),
                        TextInput::make('device_name')
                            ->label(__('admin.device_name'))
                            ->maxLength(255),
                        TextInput::make('registered_ip')->visibleOn('create')
                            ->label(__('admin.registered_ip'))
                            ->required()
                            ->rules(['ip']),
                    ]),
                Section::make(__('admin.player_subscription_section'))->columnSpanFull()
                    ->visible(fn (?Device $record): bool => ! $record || $record->login_method === 'activation')
                    ->columns(2)
                    ->schema([
                        TextInput::make('iptv_username')
                            ->label(__('admin.iptv_username'))->required(fn (Get $get): bool => filled($get('subscription_action')) && $get('subscription_action') !== 'none'),
                        TextInput::make('iptv_password')
                            ->label(__('admin.players_password'))
                            ->password()
                            ->revealable()->required(fn (Get $get, ?Device $record): bool => filled($get('subscription_action')) && $get('subscription_action') !== 'none' && ! $record?->hasPlayerCredentials())
                            ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                            ->dehydrated(fn ($state): bool => filled($state)),
                        TextInput::make('player_api_base_url')
                            ->label(__('admin.default_player_api_url'))
                            ->url()
                            ->maxLength(255)
                            ->default(fn () => SiteSetting::defaultPlayerApiUrl())
                            ->afterStateHydrated(function (TextInput $component, $state): void {
                                if (blank($state) || parse_url((string) $state, PHP_URL_HOST) === 'example.invalid') {
                                    $component->state(SiteSetting::defaultPlayerApiUrl());
                                }
                            })
                            ->helperText(__('admin.player_url_optional')),
                    ]),
                Section::make(__('workflow.subscription'))->columnSpanFull()->columns(2)
                    ->visibleOn('create')
                    ->schema([
                        Select::make('subscription_action')->label(__('workflow.subscription_action'))
                            ->options(fn (?Device $record): array => $record?->expires_at
                                ? ['none' => __('workflow.keep'), 'renew' => __('admin.subscription_renew_action')]
                                : ['none' => __('workflow.without'), 'activate' => __('admin.subscription_activate_action')])
                            ->default(fn (?Device $record): string => $record ? 'none' : 'activate')->required()->live(),
                        TextInput::make('months')->label(__('admin.subscription_months'))->integer()->minValue(1)->maxValue(120)->default(12)
                            ->required()->visible(fn (Get $get): bool => filled($get('subscription_action')) && $get('subscription_action') !== 'none'),
                        Select::make('plan')->label(__('admin.subscription_plan'))
                            ->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn (SubscriptionPlan $plan): array => [$plan->value => $plan->label()])->all())
                            ->default(fn (?Device $record): string => $record?->subscription_plan?->value ?? SubscriptionPlan::Standard->value)
                            ->required()->visible(fn (Get $get): bool => filled($get('subscription_action')) && $get('subscription_action') !== 'none'),
                        TextInput::make('amount')->label(__('admin.subscription_amount'))->numeric()->minValue(0)->step(0.01)
                            ->visible(fn (Get $get): bool => filled($get('subscription_action')) && $get('subscription_action') !== 'none'),
                        TextInput::make('payment_ref')->label(__('admin.subscription_payment_ref'))->maxLength(128)
                            ->visible(fn (Get $get): bool => filled($get('subscription_action')) && $get('subscription_action') !== 'none'),
                        Textarea::make('notes')->label(__('admin.subscription_notes'))->maxLength(1000)
                            ->visible(fn (Get $get): bool => filled($get('subscription_action')) && $get('subscription_action') !== 'none'),
                    ]),
                Section::make(__('admin.internal_notes'))->collapsible()->collapsed()
                    ->schema([
                        Textarea::make('admin_note')
                            ->label(__('admin.staff_notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
