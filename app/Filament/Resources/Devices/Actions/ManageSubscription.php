<?php

namespace App\Filament\Resources\Devices\Actions;

use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Device;
use App\Models\SiteSetting;
use App\Services\SaveDeviceSubscription;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class ManageSubscription
{
    public static function make(): Action
    {
        return Action::make('subscription')
            ->label(fn (Device $record): string => __($record->expires_at ? 'admin.subscription_renew_action' : 'admin.subscription_activate_action'))
            ->icon(Heroicon::Bolt)->color('success')
            ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
            ->visible(fn (Device $record): bool => ! $record->isdeleted && $record->login_method === 'activation' && $record->status !== DeviceStatus::Blocked)
            ->modalSubmitActionLabel(fn (Device $record): string => __($record->expires_at ? 'workflow.confirm_renew' : 'workflow.confirm_activate'))
            ->schema([
                Section::make(__('workflow.current'))->description(__('workflow.read_only'))->columns(2)->schema([
                    TextEntry::make('subscriber_summary')->label(__('control.subscriber'))->state(fn (Device $record) => $record->subscriber_name ?: $record->device_name),
                    TextEntry::make('expiry_summary')->label(__('admin.subscription_expires'))->state(fn (Device $record) => $record->expires_at?->format('Y-m-d') ?? __('admin.subscription_none')),
                ]),
                Section::make(__('workflow.choose_subscription'))->columns(2)->schema([
                    Select::make('months')->label(__('admin.subscription_months'))->options([1 => '1', 3 => '3', 6 => '6', 12 => '12', 24 => '24'])->default(12)->required()->live(),
                    Select::make('plan')->label(__('admin.subscription_plan'))->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn (SubscriptionPlan $plan) => [$plan->value => $plan->label()])->all())
                        ->default(fn (Device $record) => $record->subscription_plan?->value ?? 'standard')->required(),
                    TextEntry::make('new_expiry')->label(__('workflow.new_expiry'))->columnSpanFull()
                        ->state(fn (Device $record, Get $get) => ($record->expires_at?->isFuture() ? $record->expires_at->copy() : now())->addMonths((int) ($get('months') ?: 12))->format('Y-m-d')),
                ]),
                Section::make(__('workflow.missing_credentials'))->description(__('workflow.credentials_once'))->columns(2)
                    ->visible(fn (Device $record): bool => ! $record->hasPlayerCredentials())->schema([
                        TextInput::make('iptv_username')->label(__('admin.iptv_username'))->default(fn (Device $record) => $record->iptv_username)->required()->maxLength(255),
                        TextInput::make('iptv_password')->label(__('admin.players_password'))->password()->revealable()->required(),
                        TextInput::make('player_api_base_url')->label(__('admin.default_player_api_url'))->url()->maxLength(255)
                            ->default(fn () => SiteSetting::defaultPlayerApiUrl())->helperText(__('admin.player_url_optional'))->columnSpanFull(),
                    ]),
                Section::make(__('workflow.optional_payment'))->collapsible()->collapsed()->columns(2)->schema([
                    TextInput::make('amount')->label(__('admin.subscription_amount'))->numeric()->minValue(0),
                    TextInput::make('payment_ref')->label(__('admin.subscription_payment_ref'))->maxLength(128),
                    Textarea::make('notes')->label(__('admin.subscription_notes'))->maxLength(1000)->columnSpanFull(),
                ]),
            ])
            ->action(function (Device $record, array $data): void {
                // Derive the operation from the saved record, never from a client input.
                $data['subscription_action'] = $record->expires_at ? 'renew' : 'activate';
                app(SaveDeviceSubscription::class)->save($record, $data);
            })
            ->successNotificationTitle(__('workflow.subscription_saved'))->after(fn (Action $action) => $action->success());
    }
}
