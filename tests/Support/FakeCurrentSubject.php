<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use NetCode\Access\Application\Ports\CurrentSubject;

final class FakeCurrentSubject implements CurrentSubject
{
    public function __construct(
        private string|null $id = null,
    ) {}

    public function id(): string|null
    {
        return $this->id;
    }

    public function becomes(string|null $id): void
    {
        $this->id = $id;
    }
}
