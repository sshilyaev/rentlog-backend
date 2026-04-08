<?php

namespace App\Property\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateInvitationRequestDto
{
    #[Assert\Positive]
    public int $expiresInHours = 72;
}
