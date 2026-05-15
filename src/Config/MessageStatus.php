<?php

declare(strict_types=1);

namespace App\Config;

enum MessageStatus: string
{
    case Unread = 'unread';
    case Read = 'read';
    case Archived = 'archived';
}
