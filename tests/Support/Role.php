<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use BackedEnum;

enum Role: string
{
    case GlobalAdmin = 'global-admin';
    case Manager = 'manager';
    case Customer = 'customer';

    public function equals(string|BackedEnum $other): bool
    {
        return $this->value === ($other instanceof BackedEnum ? (string) $other->value : $other);
    }
}
