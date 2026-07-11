<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

enum Permission: string
{
    case InvoicesIssue = 'invoices.issue';
    case UsersInvite = 'users.invite';
    case UsersRemove = 'users.remove';
}
