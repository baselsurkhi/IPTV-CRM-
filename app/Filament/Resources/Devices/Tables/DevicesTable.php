<?php

namespace App\Filament\Resources\Devices\Tables;

use App\Authorization\PermissionsRegistry;
use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Filament\Resources\Devices\Actions\ManageSubscription;
use App\Filament\Resources\Devices\DeviceResource;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('subscriber_name')->label(__('control.subscriber'))->searchable()->placeholder('—'),
                TextColumn::make('login_method')->label(__('control.method'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('control.'.$state)),
                TextColumn::make('platform')->label(__('control.platform'))->badge()->placeholder('—'),
                TextColumn::make('last_seen_at')->label(__('control.last_seen'))->since()->sortable()
                    ->description(fn (Device $record): string => $record->last_seen_at?->gt(now()->subSeconds(90)) && $record->tokens()->exists() ? __('control.recently_online') : __('control.offline')),
                TextColumn::make('app_version')->label(__('control.version'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('public_ip')->label(__('control.public_ip'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('device_id')
                    ->label('Device ID')->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Medium),
                TextColumn::make('device_name')
                    ->label(__('admin.device_name'))
                    ->searchable(),
                TextColumn::make('registered_ip')
                    ->label(__('admin.registered_ip'))->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('last_seen_ip')
                    ->label(__('admin.last_ip'))
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->label(__('admin.iptv_username'))->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subscription_plan')
                    ->label(__('admin.subscription_plan'))
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionPlan $state): string => $state?->label() ?? '—')
                    ->color(fn (?SubscriptionPlan $state): string => $state?->color() ?? 'gray')
                    ->toggleable(),
                TextColumn::make('subscription_state')
                    ->label(__('admin.subscription_status'))->toggleable()
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
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('m3u_source_url')
                    ->label(__('control.m3u_source_url'))
                    ->placeholder('—')->copyable()->limit(35)
                    ->tooltip(fn (Device $record): ?string => $record->m3u_source_url)
                    ->extraAttributes(['dir' => 'ltr'])->toggleable(),
                TextColumn::make('xtream_server_url')
                    ->label(__('control.xtream_server_url'))
                    ->placeholder('—')->searchable()->copyable()->limit(35)
                    ->tooltip(fn (Device $record): ?string => $record->xtream_server_url)
                    ->extraAttributes(['dir' => 'ltr'])->toggleable(),
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
                SelectFilter::make('subscription_plan')->label(__('admin.subscription_plan'))
                    ->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn (SubscriptionPlan $plan): array => [$plan->value => $plan->label()])->all()),
                SelectFilter::make('subscription_status')->label(__('admin.subscription_filter_status'))
                    ->options([
                        'active' => __('admin.subscription_active'),
                        'expiring_soon' => __('admin.subscription_expiring'),
                        'expired' => __('admin.subscription_expired'),
                        'none' => __('admin.subscription_none'),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        match ($data['value'] ?? null) {
                            'active' => $query->where('expires_at', '>', now()),
                            'expiring_soon' => $query->whereBetween('expires_at', [now(), now()->addDays(7)]),
                            'expired' => $query->where('expires_at', '<=', now()),
                            'none' => $query->whereNull('expires_at'),
                            default => null,
                        };
                    }),
                SelectFilter::make('login_method')->label(__('control.method'))->options([
                    'activation' => __('control.activation'), 'xtream' => 'Xtream', 'm3u' => 'M3U',
                ]),
                Filter::make('online')->label(__('control.recently_online'))
                    ->query(fn ($query) => $query->where('last_seen_at', '>', now()->subSeconds(90))->whereHas('tokens')),
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
                ManageSubscription::make(),
                Action::make('setup')
                    ->label(__('workflow.manage'))
                    ->icon(Heroicon::PencilSquare)
                    ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                    ->visible(fn (Device $record): bool => ! $record->isdeleted)
                    ->url(fn (Device $record): string => DeviceResource::getUrl('edit', ['record' => $record])),
                Action::make('restore')
                    ->label(__('workflow.restore'))->icon(Heroicon::ArrowPath)->color('success')
                    ->requiresConfirmation()
                    ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                    ->visible(fn (Device $record): bool => $record->isdeleted)
                    ->action(fn (Device $record): bool => $record->update(['isdeleted' => false])),

                ActionGroup::make([
                    Action::make('setExpiry')
                        ->label(__('admin.subscription_set_expiry_action'))
                        ->icon(Heroicon::Calendar)
                        ->color('info')
                        ->visible(fn (Device $record): bool => ! $record->isdeleted && $record->login_method === 'activation')
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
                        ->visible(fn (Device $record): bool => ! $record->isdeleted && $record->login_method === 'activation')
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

                    Action::make('history')->label(__('control.history'))->icon(Heroicon::Clock)
                        ->authorize(fn (Device $record): bool => Gate::allows('view', $record))
                        ->modalSubmitAction(false)->modalCancelActionLabel(__('control.close'))
                        ->modalContent(fn (Device $record) => view('admin.device-history', ['events' => DB::table('device_events')->leftJoin('users', 'users.id', '=', 'device_events.actor_id')->where('device_events.device_id', $record->id)->select('device_events.*', 'users.name as actor_name')->orderByDesc('device_events.id')->limit(50)->get()])),
                    Action::make('unblock')->label(__('control.unblock'))->icon(Heroicon::CheckCircle)
                        ->color('success')->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->visible(fn (Device $record): bool => ! $record->isdeleted && $record->status === DeviceStatus::Blocked)
                        ->action(fn (Device $record) => $record->unblock()),
                    Action::make('reject')
                        ->label(__('admin.reject_action'))
                        ->icon(Heroicon::XCircle)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->visible(fn (Device $record): bool => ! $record->isdeleted && $record->status === DeviceStatus::Pending)
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
                        ->visible(fn (Device $record): bool => ! $record->isdeleted && $record->status !== DeviceStatus::Blocked)
                        ->action(function (Device $record): void {
                            $record->update([
                                'status' => DeviceStatus::Blocked,
                                'blocked_at' => now(),
                            ]);
                            $record->revokeAllTokens();
                        }),

                    Action::make('revokeSessions')
                        ->label(__('admin.revoke_sessions_action'))
                        ->modalDescription(__('control.kick_help'))
                        ->icon(Heroicon::ArrowRight)
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record))
                        ->action(fn (Device $record) => $record->revokeAllTokens()),

                    EditAction::make()->visible(fn (Device $record): bool => ! $record->isdeleted)
                        ->authorize(fn (Device $record): bool => Gate::allows('update', $record)),

                    Action::make('softDelete')
                        ->visible(fn (Device $record): bool => ! $record->isdeleted)
                        ->label(__('filament-actions::delete.single.label'))
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize(fn (Device $record): bool => Gate::allows('delete', $record))
                        ->action(fn (Device $record): bool => $record->moveToDeleted()),
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
                            $records->each->moveToDeleted();
                        }),
                ]),
            ]);
    }
}
