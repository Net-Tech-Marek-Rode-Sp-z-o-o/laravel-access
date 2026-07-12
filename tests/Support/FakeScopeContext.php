<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use NetCode\Access\Application\Ports\ScopeContext;

final class FakeScopeContext implements ScopeContext
{
    public function __construct(
        private string|null $scopeId = null,
    ) {}

    public function current(): string|null
    {
        return $this->scopeId;
    }

    public function enters(string|null $scopeId): void
    {
        $this->scopeId = $scopeId;
    }
}
