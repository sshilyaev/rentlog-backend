<?php

namespace App\Billing\Domain\Enum;

enum BillingCategory: string
{
    case Rent = 'rent';
    case Utility = 'utility';
    case Service = 'service';
    case Other = 'other';

    public function labelRu(): string
    {
        return match ($this) {
            self::Rent => 'Аренда',
            self::Utility => 'Коммунальные / ресурсы',
            self::Service => 'Услуги',
            self::Other => 'Прочие платежи',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $item): string => $item->value, self::cases());
    }
}
