<?php

namespace App\Auth\Domain\Exception;

final class UserAlreadyExistsException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Пользователь с email "%s" уже существует.', $email));
    }
}
