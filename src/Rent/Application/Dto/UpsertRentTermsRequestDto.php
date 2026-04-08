<?php

namespace App\Rent\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class UpsertRentTermsRequestDto
{
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
    public ?string $baseRentAmount = null;

    #[Assert\Length(min: 3, max: 3)]
    public ?string $currency = null;

    #[Assert\Range(min: 1, max: 31)]
    public ?int $billingDay = null;

    #[Assert\Date]
    public ?string $startsAt = null;

    #[Assert\Date]
    public ?string $endsAt = null;

    #[Assert\Length(max: 5000)]
    public ?string $notes = null;

    #[Assert\Choice(choices: ['active', 'archived'])]
    public ?string $status = null;
}
