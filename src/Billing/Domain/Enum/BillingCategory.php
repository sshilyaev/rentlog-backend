<?php

namespace App\Billing\Domain\Enum;

enum BillingCategory: string
{
    case Rent = 'rent';
    case Utility = 'utility';
    case Service = 'service';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $item): string => $item->value, self::cases());
    }
}
