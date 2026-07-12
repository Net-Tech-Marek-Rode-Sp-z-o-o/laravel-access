<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Data;

use NetCode\Access\Domain\ValueObjects\RoleName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

final class CreateRoleData extends Data
{
    public function __construct(
        #[Max(RoleName::MAX_LENGTH)]
        public string $name,
        #[Max(255)]
        public string $label,
    ) {}
}
