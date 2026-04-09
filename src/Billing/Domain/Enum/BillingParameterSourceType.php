<?php

namespace App\Billing\Domain\Enum;

enum BillingParameterSourceType: string
{
    case Meter = 'meter';
    case Fixed = 'fixed';

    
    public static function values(): array
    {
        return array_map(static fn (self $item): string => $item->value, self::cases());
    }
}
