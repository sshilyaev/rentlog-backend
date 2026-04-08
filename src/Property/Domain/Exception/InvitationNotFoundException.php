<?php

namespace App\Property\Domain\Exception;

final class InvitationNotFoundException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Приглашение не найдено.');
    }
}
