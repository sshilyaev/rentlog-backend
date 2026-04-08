<?php

namespace App\Property\Domain\Exception;

final class PropertyNotFoundException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Объект не найден.');
    }
}
