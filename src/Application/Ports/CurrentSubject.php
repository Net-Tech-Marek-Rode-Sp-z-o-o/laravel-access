<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Ports;

interface CurrentSubject
{
    public function id(): string|null;
}
