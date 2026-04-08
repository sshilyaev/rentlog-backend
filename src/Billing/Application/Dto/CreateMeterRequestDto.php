<?php

namespace App\Billing\Application\Dto;

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
    #[Assert\Length(min: 1, max: 50)]
    public string $unit = '';
}
