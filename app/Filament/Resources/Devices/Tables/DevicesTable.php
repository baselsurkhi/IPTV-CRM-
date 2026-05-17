<?php

namespace App\Filament\Resources\Devices\Tables;

use App\Authorization\PermissionsRegistry;
use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DevicesTable
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
                TextColumn::make('registered_ip')
                    ->label(__('admin.registered_ip'))
                    ->searchable(),
                TextColumn::make('last_seen_ip')
                    ->label(__('admin.last_ip'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.status'))
                    ->badge()
                    ->formatStateUsing(fn (DeviceStatus $state): string => $state->label())
                    ->color(fn (DeviceStatus $state): string => match ($state) {
                        DeviceStatus::Pending => 'warning',
                        DeviceStatus::Approved => 'success',
                        DeviceStatus::Rejected => 'gray',
                        DeviceStatus::Blocked => 'danger',
                    }),
                TextColumn::make('iptv_username')
                    ->label(__('admin.iptv_username'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('subscription_plan')
                    ->label(__('admin.subscription_plan'))
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionPlan $state): string => $state?->label() ?? '—')
                    ->color(fn (?SubscriptionPlan $state): string => $state?->color() ?? 'gray')
                    ->toggleable(),
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
                    })
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label(__('admin.subscription_expires'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn (Device $record): string => match ($record->subscriptionStatus()) {
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('last_login_at')
                    ->label(__('admin.last_login'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')
                    ->label(__('admin.approved_at'))
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
                SelectFilter::make('status')
                    ->label(__('admin.devices_filter_status'))
                    ->options(
                        collect(DeviceStatus::cases())->mapWithKeys(fn (DeviceStatus $s): array => [
                            $s->value => $s->label(),
                        ])->all()
                    ),
            ])
            ->striped()
            ->paginated([25, 50, 100])
            ->recordActions([
                Action::make('approve')
                    ->label(__('admin.approve_action'))
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                    ->schema([
                        TextInput::make('iptv_username')
                            ->label(__('admin.players_username'))
                            ->required(),
                        TextInput::make('iptv_password')
                            ->label(__('admin.players_password'))
                            ->password()
                            ->required(),
                        TextInput::make('player_api_base_url')
                            ->label(__('admin.player_api_url_modal'))
                            ->url()
                            ->default(config('iptv.player_api_base_url')),
                    ])
                    ->visible(fn (Device $record): bool => in_array($record->status, [DeviceStatus::Pending, DeviceStatus::Rejected], true))
                    ->action(function (Device $record, array $data): void {
                        $record->revokeAllTokens();

                        $url = isset($data['player_api_base_url']) && is_string($data['player_api_base_url']) && $data['player_api_base_url'] !== ''
                            ? rtrim($data['player_api_base_url'], '?')
                            : null;

                        $record->update([
                            'status' => DeviceStatus::Approved,
                            'iptv_username' => $data['iptv_username'],
                            'iptv_password' => $data['iptv_password'],
                            'player_api_base_url' => $url,
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                            'rejected_at' => null,
                            'blocked_at' => null,
                            'failed_login_attempts' => 0,
                            'locked_until' => null,
                        ]);

                        if ($record->isSubscribed()) {
                            $record->approveForApi(Auth::id());
                        }
                    }),

                ActionGroup::make([
                    Action::make('reject')
                        ->label(__('admin.reject_action'))
                        ->icon(Heroicon::XCircle)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->visible(fn (Device $record): bool => $record->status === DeviceStatus::Pending)
                        ->action(function (Device $record): void {
                            $record->update([
                                'status' => DeviceStatus::Rejected,
                                'rejected_at' => now(),
                                'iptv_username' => null,
                                'iptv_password' => null,
                                'player_api_base_url' => null,
                                'approved_at' => null,
                                'approved_by' => null,
                            ]);
                            $record->revokeAllTokens();
                        }),

                    Action::make('block')
                        ->label(__('admin.block_action'))
                        ->icon(Heroicon::NoSymbol)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->visible(fn (Device $record): bool => $record->status !== DeviceStatus::Blocked)
                        ->action(function (Device $record): void {
                            $record->update([
                                'status' => DeviceStatus::Blocked,
                                'blocked_at' => now(),
                            ]);
                            $record->revokeAllTokens();
                        }),

                    Action::make('revokeSessions')
                        ->label(__('admin.revoke_sessions_action'))
                        ->icon(Heroicon::ArrowRight)
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->action(fn (Device $record) => $record->revokeAllTokens()),

                    EditAction::make()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record)),

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
}
