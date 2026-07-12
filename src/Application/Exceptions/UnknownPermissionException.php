<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class UnknownPermissionException extends DomainException
{
    /** @param list<string> $permissions */
    public static function for(array $permissions): self
    {
        return new self(sprintf(
            'Permission %s is not declared by the application.',
            implode(', ', array_map(static fn (string $permission): string => sprintf('<%s>', $permission), $permissions)),
        ));
    }
}
