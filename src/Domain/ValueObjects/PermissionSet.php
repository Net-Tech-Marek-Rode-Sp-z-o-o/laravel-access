<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\ValueObjects;

use BackedEnum;
use Countable;
use NetCode\Domain\ValueObject;

final class PermissionSet extends ValueObject implements Countable
{
    /** @var list<string> */
    private readonly array $permissions;

    /** @param list<string> $permissions */
    private function __construct(
        array $permissions,
    ) {
        $unique = array_values(array_unique($permissions));
        sort($unique);

        $this->permissions = $unique;
    }

    /** @param iterable<string|BackedEnum|PermissionId> $permissions */
    public static function from(iterable $permissions): self
    {
        $values = [];

        foreach ($permissions as $permission) {
            $values[] = PermissionId::from($permission)->value();
        }

        return new self($values);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function contains(PermissionId $permission): bool
    {
        return in_array($permission->value(), $this->permissions, strict: true);
    }

    /** @return list<string> */
    public function toStrings(): array
    {
        return $this->permissions;
    }

    public function isEmpty(): bool
    {
        return $this->permissions === [];
    }

    public function count(): int
    {
        return count($this->permissions);
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->permissions === $this->permissions;
    }
}
