<?php

namespace App\Property\Domain\Exception;

final class PropertyAccessDeniedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('У вас нет доступа к этому объекту.');
    }
}
