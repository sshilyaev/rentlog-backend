<?php

namespace App\Billing\Application\Dto;

use App\Billing\Domain\Enum\BillingCategory;
use App\Billing\Domain\Enum\BillingParameterSourceType;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateBillingParameterRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $code = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $title = '';

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [BillingCategory::class, 'values'])]
    public string $category = '';

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [BillingParameterSourceType::class, 'values'])]
    public string $sourceType = '';

    #[Assert\Length(max: 50)]
    public ?string $unit = null;

    #[Assert\Uuid]
    public ?string $meterId = null;
}
