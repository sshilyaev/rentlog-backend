<?php

namespace App\Property\Domain\Exception;

final class InvitationMemberAlreadyLinkedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Участник уже связан с другим аккаунтом.');
    }
}
