<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('device.status.pending'),
            self::Approved => __('device.status.approved'),
            self::Rejected => __('device.status.rejected'),
            self::Blocked => __('device.status.blocked'),
        };
    }
}
