<?php

namespace App\Billing\Application\Dto;

use App\Billing\Domain\Enum\MeterUnit;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateMeterRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $code = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $title = '';

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [self::class, 'allowedUnits'])]
    public string $unit = '';

    /** @return list<string> */
    public static function allowedUnits(): array
    {
        return array_map(static fn (MeterUnit $u): string => $u->value, MeterUnit::cases());
    }
}
