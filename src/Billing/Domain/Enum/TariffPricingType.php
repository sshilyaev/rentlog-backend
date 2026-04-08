<?php

namespace App\Billing\Domain\Enum;

enum TariffPricingType: string
{
    case PerUnit = 'per_unit';
    case FixedAmount = 'fixed_amount';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $item): string => $item->value, self::cases());
    }
}
