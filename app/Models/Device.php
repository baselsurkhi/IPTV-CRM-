<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Concerns\HasSubscription;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'device_id',
    'device_name',
    'subscriber_name',
    'registered_ip',
    'last_seen_ip',
    'status',
    'iptv_username',
    'iptv_password',
    'player_api_base_url',
    'admin_note',
    'approved_at',
    'approved_by',
    'rejected_at',
    'blocked_at',
    'failed_login_attempts',
    'locked_until',
    'last_login_at',
    'subscribed_at',
    'expires_at',
    'subscription_months',
    'subscription_plan',
    'subscription_notes',
    'isdeleted',
])]
class Device extends Model
{
    use HasApiTokens, HasSubscription;

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'iptv_password' => 'encrypted',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'blocked_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'expires_at' => 'datetime',
            'subscription_plan' => SubscriptionPlan::class,
            'isdeleted' => 'boolean',
        ];
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    public function hasPlayerCredentials(): bool
    {
        try {
            $password = $this->iptv_password;
        } catch (\Throwable) {
            $password = null;
        }

        return filled($this->iptv_username) && is_string($password) && filled($password);
    }

    public function isApiReady(): bool
    {
        return $this->status === DeviceStatus::Approved
            && $this->isSubscribed()
            && $this->hasPlayerCredentials();
    }

    public function apiAccessState(): string
    {
        if ($this->status === DeviceStatus::Blocked) {
            return 'blocked';
        }

        if ($this->expires_at === null) {
            return 'needs_subscription';
        }

        if (! $this->isSubscribed()) {
            return 'expired';
        }

        if (! $this->hasPlayerCredentials()) {
            return 'needs_credentials';
        }

        if ($this->status !== DeviceStatus::Approved) {
            return 'waiting_approval';
        }

        return 'ready';
    }

    public function approveForApi(?int $approvedBy = null): void
    {
        if ($this->status === DeviceStatus::Blocked || ! $this->hasPlayerCredentials()) {
            return;
        }

        $this->forceFill([
            'status' => DeviceStatus::Approved,
            'approved_at' => $this->approved_at ?? now(),
            'approved_by' => $this->approved_by ?? $approvedBy,
            'rejected_at' => null,
            'blocked_at' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    public function playerApiUrl(): ?string
    {
        if (! $this->hasPlayerCredentials()) {
            return null;
        }

        $globalBase = SiteSetting::getValue('iptv.player_api_base_url');
        $base = $this->player_api_base_url ?: ($globalBase ?: config('iptv.player_api_base_url'));

        return self::normalizePlayerApiBaseUrl((string) $base).'?'.http_build_query([
            'username' => $this->iptv_username,
            'password' => $this->iptv_password,
        ]);
    }

    private static function normalizePlayerApiBaseUrl(string $base): string
    {
        $base = trim($base);
        $base = preg_replace('/[?#].*$/', '', $base) ?: $base;
        $base = preg_replace('/^http:\/\//i', 'https://', $base) ?: $base;

        return rtrim($base, '/?');
    }
}
