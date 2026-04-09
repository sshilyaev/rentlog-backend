<?php

namespace App\Property\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreatePropertyRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $title = '';

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [self::class, 'availableTypes'])]
    public string $typeCode = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    public string $address = '';

    #[Assert\Length(max: 5000)]
    public ?string $description = null;

    #[Assert\Type('array')]
    public array $metadata = [];

    
    public static function availableTypes(): array
    {
        return \App\Property\Domain\Enum\PropertyType::values();
    }
}
