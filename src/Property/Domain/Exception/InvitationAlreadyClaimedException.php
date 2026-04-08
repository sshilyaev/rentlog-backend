<?php

namespace App\Property\Domain\Exception;

final class InvitationAlreadyClaimedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Приглашение уже было использовано.');
    }
}
