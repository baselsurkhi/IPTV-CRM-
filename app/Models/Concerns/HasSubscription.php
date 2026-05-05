<?php

namespace App\Models\Concerns;

use App\Enums\DeviceStatus;
use App\Enums\SubscriptionPlan;
use App\Models\SubscriptionRenewal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subscription behaviour for Device model.
 *
 * Usage in your Device model:
 *   use HasSubscription;
 *
 * Add to Device $casts:
 *   'subscribed_at'     => 'datetime',
 *   'expires_at'        => 'datetime',
 *   'subscription_plan' => SubscriptionPlan::class,
 *
 * Add to Device $fillable:
 *   'subscribed_at', 'expires_at', 'subscription_months',
 *   'subscription_plan', 'subscription_notes',
 *
 * @property Carbon|null $subscribed_at
 * @property Carbon|null $expires_at
 * @property int $subscription_months
 * @property SubscriptionPlan|null $subscription_plan
 * @property string|null $subscription_notes
 *
 * @mixin Model
 */
trait HasSubscription
{
    // Relations

    public function renewals(): HasMany
    {
        return $this->hasMany(SubscriptionRenewal::class)->latest();
    }

    // Computed helpers

    public function isSubscribed(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function daysRemaining(): int
    {
        if (! $this->expires_at || $this->expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at);
    }

    public function expiresInDays(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function subscriptionStatus(): string
    {
        if (! $this->expires_at) {
            return 'none';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->daysRemaining() <= 7) {
            return 'expiring_soon';
        }

        return 'active';
    }

    // Actions

    /**
     * Activate a new subscription (or reset an expired one).
     * Starts from today.
     */
    public function activateSubscription(
        int $months = 12,
        SubscriptionPlan $plan = SubscriptionPlan::Standard,
        ?string $notes = null,
        ?string $renewedBy = null,
        ?float $amount = null,
        string $currency = 'USD',
        ?string $paymentRef = null,
    ): SubscriptionRenewal {
        $previous = $this->expires_at;
        $newExpiry = Carbon::now()->addMonths($months);

        $this->subscribed_at = $this->subscribed_at ?? now();
        $this->expires_at = $newExpiry;
        $this->subscription_months = $months;
        $this->subscription_plan = $plan;
        $this->subscription_notes = $notes;
        $this->save();
        $this->syncApiApprovalAfterSubscription();

        return $this->renewals()->create([
            'plan' => $plan->value,
            'months' => $months,
            'previous_expires_at' => $previous,
            'new_expires_at' => $newExpiry,
            'amount' => $amount,
            'currency' => $currency,
            'payment_ref' => $paymentRef,
            'renewed_by' => $renewedBy,
            'notes' => $notes,
        ]);
    }

    /**
     * Renew an existing subscription.
     * If still active, extends from current expiry.
     * If expired, starts from today.
     */
    public function renewSubscription(
        int $months = 12,
        ?SubscriptionPlan $plan = null,
        ?string $notes = null,
        ?string $renewedBy = null,
        ?float $amount = null,
        string $currency = 'USD',
        ?string $paymentRef = null,
    ): SubscriptionRenewal {
        $previous = $this->expires_at;
        $base = ($previous && $previous->isFuture()) ? $previous : Carbon::now();
        $newExpiry = $base->copy()->addMonths($months);

        $this->expires_at = $newExpiry;
        $this->subscription_months = $months;
        if ($plan) {
            $this->subscription_plan = $plan;
        }
        if ($notes) {
            $this->subscription_notes = $notes;
        }
        $this->save();
        $this->syncApiApprovalAfterSubscription();

        return $this->renewals()->create([
            'plan' => ($plan ?? $this->subscription_plan)->value,
            'months' => $months,
            'previous_expires_at' => $previous,
            'new_expires_at' => $newExpiry,
            'amount' => $amount,
            'currency' => $currency,
            'payment_ref' => $paymentRef,
            'renewed_by' => $renewedBy,
            'notes' => $notes,
        ]);
    }

    /**
     * Manually set an expiry date (admin override).
     */
    public function setExpiryDate(
        Carbon $date,
        ?string $renewedBy = null,
        ?string $notes = null,
    ): SubscriptionRenewal {
        $previous = $this->expires_at;
        $this->expires_at = $date;
        $this->save();
        $this->syncApiApprovalAfterSubscription();

        return $this->renewals()->create([
            'plan' => ($this->subscription_plan ?? SubscriptionPlan::Standard)->value,
            'months' => 0,
            'previous_expires_at' => $previous,
            'new_expires_at' => $date,
            'renewed_by' => $renewedBy,
            'notes' => $notes ?? 'Manual date override',
        ]);
    }

    /**
     * Suspend / cancel a subscription.
     */
    public function cancelSubscription(?string $renewedBy = null, ?string $notes = null): void
    {
        $this->renewals()->create([
            'plan' => ($this->subscription_plan ?? SubscriptionPlan::Standard)->value,
            'months' => 0,
            'previous_expires_at' => $this->expires_at,
            'new_expires_at' => now(),
            'renewed_by' => $renewedBy,
            'notes' => $notes ?? 'Subscription cancelled',
        ]);

        $this->expires_at = now();
        $this->save();

        if (method_exists($this, 'revokeAllTokens')) {
            $this->revokeAllTokens();
        }
    }

    private function syncApiApprovalAfterSubscription(): void
    {
        if (! $this->isSubscribed() || ! method_exists($this, 'hasPlayerCredentials')) {
            return;
        }

        if (! $this->hasPlayerCredentials() || ($this->status ?? null) === DeviceStatus::Blocked) {
            return;
        }

        if (method_exists($this, 'approveForApi')) {
            $this->approveForApi(auth()->id());
        }
    }
}
