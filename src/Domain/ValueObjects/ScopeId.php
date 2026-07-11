<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\ValueObjects;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Domain\ValueObject;
use Stringable;

final class ScopeId extends ValueObject implements Stringable
{
    public const int MAX_LENGTH = 64;

    private readonly string $value;

    public function __construct(
        string $value,
    ) {
        $normalised = trim($value);

        if ($normalised === '' || mb_strlen($normalised) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('<%s> is not a valid scope id.', $value));
        }

        $this->value = $normalised;
    }

    public static function fromNullable(string|null $value): self|null
    {
        return $value === null ? null : new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
