<?php

declare(strict_types=1);

namespace App\Config;

enum PackageSlug: string
{
    case Starter = 'starter';
    case Standard = 'standard';
    case Premium = 'premium';
}
