<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\Exceptions;

use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Domain\Exception\DomainException;

final class RoleNotFoundException extends DomainException
{
    public static function withId(RoleId $id): self
    {
        return new self(sprintf('Role <%s> was not found.', $id->value()));
    }

    public static function withName(RoleName $name): self
    {
        return new self(sprintf('Role <%s> was not found.', $name->value()));
    }
}
