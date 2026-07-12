<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Services;

use NetCode\Access\Application\Exceptions\UnknownPermissionException;
use NetCode\Access\Application\Ports\PermissionCatalog;
use NetCode\Access\Domain\ValueObjects\PermissionSet;

final readonly class DeclaredPermissions
{
    public function __construct(
        private PermissionCatalog $catalog,
    ) {}

    public function all(): PermissionSet
    {
        return PermissionSet::from($this->catalog->all());
    }

    /** @throws UnknownPermissionException */
    public function assertDeclared(PermissionSet $permissions): void
    {
        $declared = $this->all();

        if ($declared->isEmpty()) {
            return;
        }

        $unknown = array_values(array_diff($permissions->toStrings(), $declared->toStrings()));

        if ($unknown !== []) {
            throw UnknownPermissionException::for($unknown);
        }
    }
}
