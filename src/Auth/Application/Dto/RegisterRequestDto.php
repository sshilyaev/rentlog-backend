<?php

namespace App\Auth\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $fullName = '';
}
