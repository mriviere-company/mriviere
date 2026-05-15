<?php

declare(strict_types=1);

namespace App\Config;

enum QuoteStatus: string
{
    case Pending = 'pending';
    case DepositPaid = 'deposit_paid';
    case Active = 'active';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
}
