<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRenewal extends Model
{
    protected $fillable = [
        'device_id',
        'plan',
        'months',
        'previous_expires_at',
        'new_expires_at',
        'amount',
        'currency',
        'payment_ref',
        'renewed_by',
        'notes',
    ];

    protected $casts = [
        'previous_expires_at' => 'datetime',
        'new_expires_at'      => 'datetime',
        'amount'              => 'decimal:2',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}