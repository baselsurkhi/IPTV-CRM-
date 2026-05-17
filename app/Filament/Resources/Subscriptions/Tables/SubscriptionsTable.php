<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Authorization\PermissionsRegistry;
use App\Enums\SubscriptionPlan;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Medium),
                TextColumn::make('device_name')
                    ->label(__('admin.device_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subscriber_name')
                    ->label(__('admin.subscriber_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('iptv_username')
                    ->label(__('admin.iptv_username'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('subscription_plan')
                    ->label(__('admin.subscription_plan'))
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionPlan $state): string => $state?->label() ?? '—')
                    ->color(fn (?SubscriptionPlan $state): string => $state?->color() ?? 'gray'),
                TextColumn::make('subscription_state')
                    ->label(__('admin.subscription_status'))
                    ->badge()
                    ->state(fn (Device $record): string => $record->subscriptionStatus())
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('admin.subscription_active'),
                        'expiring_soon' => __('admin.subscription_expiring'),
                        'expired' => __('admin.subscription_expired'),
                        default => __('admin.subscription_none'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('api_access_state')
                    ->label(__('admin.api_access_status'))
                    ->badge()
                    ->state(fn (Device $record): string => $record->apiAccessState())
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ready' => __('admin.api_ready'),
                        'needs_credentials' => __('admin.api_needs_credentials'),
                        'needs_subscription' => __('admin.api_needs_subscription'),
                        'expired' => __('admin.api_subscription_expired'),
                        'blocked' => __('admin.api_blocked'),
                        default => __('admin.api_waiting_approval'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ready' => 'success',
                        'needs_credentials' => 'warning',
                        'needs_subscription' => 'gray',
                        'expired', 'blocked' => 'danger',
                        default => 'info',
                    }),
                TextColumn::make('subscribed_at')
                    ->label(__('admin.subscription_activated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                // ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expires_at')
                    ->label(__('admin.subscription_expires'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn (Device $record): string => match ($record->subscriptionStatus()) {
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('registered_ip')
                    ->label(__('admin.registered_ip'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')
                    ->label(__('admin.last_login'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin.created_at_label'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_plan')
                    ->label(__('admin.subscription_plan'))
                    ->options(
                        collect(SubscriptionPlan::cases())->mapWithKeys(fn (SubscriptionPlan $plan): array => [
                            $plan->value => $plan->label(),
                        ])->all()
                    ),
                SelectFilter::make('subscription_status')
                    ->label(__('admin.subscription_filter_status'))
                    ->options([
                        'active' => __('admin.subscription_active'),
                        'expiring_soon' => __('admin.subscription_expiring'),
                        'expired' => __('admin.subscription_expired'),
                        'none' => __('admin.subscription_none'),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $value = $data['value'] ?? null;
                        if (! filled($value)) {
                            return;
                        }

                        match ($value) {
                            'active' => $query->where('expires_at', '>', now()),
                            'expiring_soon' => $query->whereBetween('expires_at', [now(), now()->addDays(7)]),
                            'expired' => $query->where('expires_at', '<=', now())->whereNotNull('expires_at'),
                            'none' => $query->whereNull('expires_at'),
                            default => null,
                        };
                    }),
            ])
            ->striped()
            ->paginated([25, 50, 100])
            ->defaultSort('expires_at', 'asc')
            ->recordActions([
                ActionGroup::make([
                    Action::make('activateSubscription')
                        ->label(__('admin.subscription_activate_action'))
                        ->icon(Heroicon::Bolt)
                        ->color('success')
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->schema([
                            TextInput::make('months')
                                ->label(__('admin.subscription_months'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(120)
                                ->default(12)
                                ->required(),
                            Select::make('plan')
                                ->label(__('admin.subscription_plan'))
                                ->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn (SubscriptionPlan $plan): array => [
                                    $plan->value => $plan->label(),
                                ])->all())
                                ->default(SubscriptionPlan::Standard->value)
                                ->required(),
                            TextInput::make('subscriber_name')
                                ->label(__('admin.subscriber_name'))
                                ->default(fn (Device $record): ?string => $record->subscriber_name)
                                ->maxLength(255),
                            TextInput::make('iptv_username')
                                ->label(__('admin.players_username'))
                                ->default(fn (Device $record): ?string => $record->iptv_username)
                                ->required(fn (Device $record): bool => ! $record->hasPlayerCredentials()),
                            TextInput::make('iptv_password')
                                ->label(__('admin.players_password'))
                                ->password()
                                ->revealable()
                                ->required(fn (Device $record): bool => ! $record->hasPlayerCredentials()),
                            TextInput::make('player_api_base_url')
                                ->label(__('admin.player_api_url_modal'))
                                ->url()
                                ->default(fn (Device $record): ?string => $record->player_api_base_url ?? config('iptv.player_api_base_url')),
                            TextInput::make('amount')
                                ->label(__('admin.subscription_amount'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01),
                            TextInput::make('payment_ref')
                                ->label(__('admin.subscription_payment_ref'))
                                ->maxLength(128),
                            Textarea::make('notes')
                                ->label(__('admin.subscription_notes'))
                                ->maxLength(1000),
                        ])
                        ->action(function (Device $record, array $data): void {
                            self::syncPlayerCredentials($record, $data);

                            $record->activateSubscription(
                                months: (int) $data['months'],
                                plan: SubscriptionPlan::from($data['plan']),
                                notes: $data['notes'] ?? null,
                                renewedBy: Auth::user()?->email,
                                amount: isset($data['amount']) ? (float) $data['amount'] : null,
                                paymentRef: $data['payment_ref'] ?? null,
                            );

                            $record->approveForApi(Auth::id());
                        }),

                    Action::make('renewSubscription')
                        ->label(__('admin.subscription_renew_action'))
                        ->icon(Heroicon::ArrowPath)
                        ->color('warning')
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->schema([
                            TextInput::make('months')
                                ->label(__('admin.subscription_months'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(120)
                                ->default(12)
                                ->required(),
                            Select::make('plan')
                                ->label(__('admin.subscription_plan'))
                                ->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn (SubscriptionPlan $plan): array => [
                                    $plan->value => $plan->label(),
                                ])->all())
                                ->placeholder(__('admin.subscription_keep_plan')),
                            TextInput::make('iptv_username')
                                ->label(__('admin.players_username'))
                                ->default(fn (Device $record): ?string => $record->iptv_username),
                            TextInput::make('iptv_password')
                                ->label(__('admin.players_password'))
                                ->password()
                                ->revealable(),
                            TextInput::make('player_api_base_url')
                                ->label(__('admin.player_api_url_modal'))
                                ->url()
                                ->default(fn (Device $record): ?string => $record->player_api_base_url ?? config('iptv.player_api_base_url')),
                            TextInput::make('amount')
                                ->label(__('admin.subscription_amount'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01),
                            TextInput::make('payment_ref')
                                ->label(__('admin.subscription_payment_ref'))
                                ->maxLength(128),
                            Textarea::make('notes')
                                ->label(__('admin.subscription_notes'))
                                ->maxLength(1000),
                        ])
                        ->action(function (Device $record, array $data): void {
                            self::syncPlayerCredentials($record, $data);

                            $record->renewSubscription(
                                months: (int) $data['months'],
                                plan: filled($data['plan'] ?? null) ? SubscriptionPlan::from($data['plan']) : null,
                                notes: $data['notes'] ?? null,
                                renewedBy: Auth::user()?->email,
                                amount: isset($data['amount']) ? (float) $data['amount'] : null,
                                paymentRef: $data['payment_ref'] ?? null,
                            );

                            $record->approveForApi(Auth::id());
                        }),

                    Action::make('setExpiry')
                        ->label(__('admin.subscription_set_expiry_action'))
                        ->icon(Heroicon::Calendar)
                        ->color('info')
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->schema([
                            DatePicker::make('expires_at')
                                ->label(__('admin.subscription_expires'))
                                ->minDate(now()->addDay())
                                ->required(),
                            Textarea::make('notes')
                                ->label(__('admin.subscription_notes'))
                                ->maxLength(1000),
                        ])
                        ->action(function (Device $record, array $data): void {
                            $record->setExpiryDate(
                                date: Carbon::parse($data['expires_at']),
                                renewedBy: Auth::user()?->email,
                                notes: $data['notes'] ?? null,
                            );
                        }),

                    Action::make('cancelSubscription')
                        ->label(__('admin.subscription_cancel_action'))
                        ->icon(Heroicon::XMark)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->schema([
                            Textarea::make('notes')
                                ->label(__('admin.subscription_notes'))
                                ->maxLength(1000),
                        ])
                        ->action(function (Device $record, array $data): void {
                            $record->cancelSubscription(
                                renewedBy: Auth::user()?->email,
                                notes: $data['notes'] ?? null,
                            );
                        }),

                    Action::make('softDelete')
                        ->label(__('filament-actions::delete.single.label'))
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('delete', $record))
                        ->action(fn (Device $record): bool => $record->update(['isdeleted' => true])),
                ])
                    ->label(__('admin.actions'))
                    ->icon(Heroicon::EllipsisVertical)
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('softDelete')
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize(fn (): bool => auth()->check() && auth()->user()?->can(
                            PermissionsRegistry::DEVICES_DELETE
                        ) === true)
                        ->action(function ($records): void {
                            $records->each->update(['isdeleted' => true]);
                        }),
                ]),
            ]);
    }

    private static function syncPlayerCredentials(Device $record, array $data): void
    {
        $updates = [];

        if (array_key_exists('iptv_username', $data) && filled($data['iptv_username'])) {
            $updates['iptv_username'] = $data['iptv_username'];
        }

        if (array_key_exists('subscriber_name', $data)) {
            $updates['subscriber_name'] = filled($data['subscriber_name'])
                ? $data['subscriber_name']
                : null;
        }

        if (array_key_exists('iptv_password', $data) && filled($data['iptv_password'])) {
            $updates['iptv_password'] = $data['iptv_password'];
        }

        if (array_key_exists('player_api_base_url', $data)) {
            $updates['player_api_base_url'] = filled($data['player_api_base_url'])
                ? rtrim((string) $data['player_api_base_url'], '?')
                : null;
        }

        if ($updates !== []) {
            $record->forceFill($updates)->save();
            $record->refresh();
        }
    }
}
