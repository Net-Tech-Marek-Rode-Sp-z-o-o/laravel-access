<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Subject;

use NetCode\Access\Application\Ports\CurrentSubject;

final class NullCurrentSubject implements CurrentSubject
{
    public function id(): string|null
    {
        return null;
    }
}
