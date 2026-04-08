<?php

namespace App\Property\Domain\Enum;

enum PropertyMemberStatus: string
{
    case Active = 'active';
    case Invited = 'invited';
    case Placeholder = 'placeholder';
    case Archived = 'archived';
}
