<?php

declare(strict_types=1);

namespace App\Auth\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 32, max: 64)]
    public string $token = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    public string $password = '';
}
