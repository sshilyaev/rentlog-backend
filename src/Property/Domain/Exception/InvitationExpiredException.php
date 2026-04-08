<?php

namespace App\Property\Domain\Exception;

final class InvitationExpiredException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Срок действия приглашения истек.');
    }
}
