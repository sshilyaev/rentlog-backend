<?php

namespace App\Rent\Domain\Enum;

enum RentTermsStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
