<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\ValueObjects;

use BackedEnum;
use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Domain\ValueObject;
use Stringable;

final class RoleName extends ValueObject implements Stringable
{
    public const int MAX_LENGTH = 64;

    private const string PATTERN = '/^[a-z0-9]([a-z0-9._-]*[a-z0-9])?$/';

    private readonly string $value;

    public function __construct(
        string $value,
    ) {
        $normalised = mb_strtolower(trim($value));

        if (mb_strlen($normalised) > self::MAX_LENGTH || preg_match(self::PATTERN, $normalised) !== 1) {
            throw new InvalidArgumentException(sprintf('<%s> is not a valid role name.', $value));
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
