<?php

declare(strict_types=1);

namespace App\Auth\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ForgotPasswordRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';
}
