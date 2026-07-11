<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

enum Role: string
{
    case GlobalAdmin = 'global-admin';
    case Manager = 'manager';
    case Customer = 'customer';
}
