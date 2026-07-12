<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Ports;

interface ScopeContext
{
    public function current(): string|null;
}
