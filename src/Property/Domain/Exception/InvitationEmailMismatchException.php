<?php

namespace App\Property\Domain\Exception;

final class InvitationEmailMismatchException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Приглашение предназначено для другого email.');
    }
}
