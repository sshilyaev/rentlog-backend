<?php

namespace App\Billing\Application\Dto;

use App\Billing\Domain\Enum\TariffPricingType;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateTariffPeriodRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [TariffPricingType::class, 'values'])]
    public string $pricingType = '';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
    public string $price = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 3)]
    public string $currency = 'RUB';

    #[Assert\NotBlank]
    #[Assert\Date]
    public string $effectiveFrom = '';

    #[Assert\Date]
    public ?string $effectiveTo = null;
}
