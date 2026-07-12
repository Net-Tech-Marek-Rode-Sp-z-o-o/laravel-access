<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\Exceptions;

use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Domain\Exception\DomainException;

final class RoleNameAlreadyTakenException extends DomainException
{
    public static function for(RoleName $name): self
    {
        return new self(sprintf('Role <%s> already exists.', $name->value()));
    }
}
