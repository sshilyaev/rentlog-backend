<?php

namespace App\Billing\Domain\Enum;

enum MeterReadingType: string
{
    case Initial = 'initial';
    case Monthly = 'monthly';
}
