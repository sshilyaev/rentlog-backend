<?php

namespace App\Billing\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateMeterReadingRequestDto
{
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,3})?$/')]
    public string $value = '';

    #[Assert\Range(min: 2020, max: 2100)]
    public ?int $billingYear = null;

    #[Assert\Range(min: 1, max: 12)]
    public ?int $billingMonth = null;

    #[Assert\Length(max: 2000)]
    public ?string $comment = null;
}
