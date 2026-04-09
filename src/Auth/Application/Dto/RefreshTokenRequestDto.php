<?php

declare(strict_types=1);

namespace App\Auth\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RefreshTokenRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 32, max: 128)]
    public string $refreshToken = '';
}
