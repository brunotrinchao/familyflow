<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case POSTED = 'Posted';
    case CANCELLED = 'Cancelled';
}
