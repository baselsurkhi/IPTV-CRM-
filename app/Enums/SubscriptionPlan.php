<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Trial   = 'trial';
    case Standard = 'standard';
    case Premium = 'premium';
    case Vip     = 'vip';

    public function label(): string
    {
        return __('crm.plans.'.$this->value);
    }

    public function defaultMonths(): int
    {
        return match ($this) {
            self::Trial    => 1,
            self::Standard => 12,
            self::Premium  => 12,
            self::Vip      => 12,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial    => 'gray',
            self::Standard => 'blue',
            self::Premium  => 'amber',
            self::Vip      => 'rose',
        };
    }
}