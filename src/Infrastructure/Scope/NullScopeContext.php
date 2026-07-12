<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Scope;

use NetCode\Access\Application\Ports\ScopeContext;

final class NullScopeContext implements ScopeContext
{
    public function current(): string|null
    {
        return null;
    }
}
