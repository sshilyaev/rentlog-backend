<?php

namespace App\Property\Domain\Exception;

final class PropertyMemberAlreadyExistsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Участник с такими данными уже существует у данного объекта.');
    }
}
