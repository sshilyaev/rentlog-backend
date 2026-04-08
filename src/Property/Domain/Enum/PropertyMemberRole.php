<?php

namespace App\Property\Domain\Enum;

enum PropertyMemberRole: string
{
    case Landlord = 'landlord';
    case Tenant = 'tenant';
}
