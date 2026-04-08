<?php

namespace App\Property\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreatePropertyMemberRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $fullName = '';

    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\Length(max: 50)]
    public ?string $phone = null;
}
