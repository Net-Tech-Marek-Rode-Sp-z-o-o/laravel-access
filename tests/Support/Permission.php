<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use BackedEnum;

enum Permission: string
{
    case InvoicesIssue = 'invoices.issue';
    case UsersInvite = 'users.invite';
    case UsersRemove = 'users.remove';
    case RolesManage = 'access.roles.manage';

    public function equals(string|BackedEnum $other): bool
    {
        return $this->value === ($other instanceof BackedEnum ? (string) $other->value : $other);
    }
}
