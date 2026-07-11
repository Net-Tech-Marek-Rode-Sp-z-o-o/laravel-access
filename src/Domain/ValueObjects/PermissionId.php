<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\ValueObjects;

use BackedEnum;
use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Domain\ValueObject;
use Stringable;

final class PermissionId extends ValueObject implements Stringable
{
    public const int MAX_LENGTH = 128;

    private readonly string $value;

    public function __construct(
        string $value,
    ) {
        $normalised = trim($value);

        if ($normalised === '' || mb_strlen($normalised) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('<%s> is not a valid permission id.', $value));
        }

        $this->value = $normalised;
    }

    public static function from(string|BackedEnum|self $value): self
    {
        return match (true) {
            $value instanceof self => $value,
            $value instanceof BackedEnum => new self((string) $value->value),
            default => new self($value),
        };
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
