<?php

namespace App\Property\Domain\Enum;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case House = 'house';
    case LandPlot = 'land_plot';
    case Garage = 'garage';
    case Office = 'office';
    case Other = 'other';

    
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases()
        );
    }
}
