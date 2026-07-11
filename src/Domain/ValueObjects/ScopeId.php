<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\ValueObjects;

use NetCode\Domain\Identifier\Uuid;

final class ScopeId extends Uuid
{
    public static function fromNullable(string|null $value): self|null
    {
        return $value === null ? null : new self($value);
    }
}
