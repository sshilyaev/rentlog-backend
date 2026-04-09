<?php

declare(strict_types=1);

namespace App\Billing\Domain\Enum;

/**
 * Код хранится в БД (value); подписи — для UI и API.
 */
enum MeterUnit: string
{
    case CubicMeter = 'm3';
    case Liter = 'l';
    case KilowattHour = 'kwh';
    case GigaCalorie = 'gcal';
    case SquareMeter = 'm2';
    case Piece = 'pc';

    public function labelRu(): string
    {
        return match ($this) {
            self::CubicMeter => 'м³',
            self::Liter => 'л',
            self::KilowattHour => 'кВт·ч',
            self::GigaCalorie => 'Гкал',
            self::SquareMeter => 'м²',
            self::Piece => 'шт.',
        };
    }

    /** @return array<string, self> code => enum */
    public static function choicesForForms(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->labelRu().' ('.$case->value.')'] = $case;
        }

        return $out;
    }

    public static function tryFromLoose(string $value): ?self
    {
        $normalized = mb_strtolower(trim($value));
        $map = [
            'м³' => self::CubicMeter,
            'm3' => self::CubicMeter,
            'м3' => self::CubicMeter,
            'куб.м' => self::CubicMeter,
            'л' => self::Liter,
            'l' => self::Liter,
            'квт·ч' => self::KilowattHour,
            'квтч' => self::KilowattHour,
            'kwh' => self::KilowattHour,
            'гкал' => self::GigaCalorie,
            'gcal' => self::GigaCalorie,
            'м²' => self::SquareMeter,
            'm2' => self::SquareMeter,
            'шт' => self::Piece,
            'шт.' => self::Piece,
            'pc' => self::Piece,
        ];

        return $map[$normalized] ?? self::tryFrom($normalized);
    }

}
