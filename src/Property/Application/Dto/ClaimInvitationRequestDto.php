<?php

namespace App\Property\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ClaimInvitationRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 64)]
    public string $code = '';
}
