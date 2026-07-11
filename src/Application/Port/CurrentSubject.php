<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Port;

interface CurrentSubject
{
    public function id(): string|null;
}
