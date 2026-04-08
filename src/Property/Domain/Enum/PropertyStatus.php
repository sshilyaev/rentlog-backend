<?php

namespace App\Property\Domain\Enum;

enum PropertyStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
