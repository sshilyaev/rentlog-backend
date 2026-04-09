<?php

namespace App\Billing\Domain\Enum;

enum BillingParameterSourceType: string
{
    /** Начисление по показаниям счётчика (вода, электричество и т.д.) */
    case Meter = 'meter';
    /** Фиксированная сумма: интернет, вывоз мусора, охрана и т.п. */
    case Fixed = 'fixed';

    public function labelRu(): string
    {
        return match ($this) {
            self::Meter => 'По счётчику',
            self::Fixed => 'Фиксированная сумма',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $item): string => $item->value, self::cases());
    }
}
