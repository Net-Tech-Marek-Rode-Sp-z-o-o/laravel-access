<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Data;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Attributes\WithoutValidation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class AssignRoleData extends Data
{
    public function __construct(
        #[Uuid]
        public string $roleId,
        #[FromRouteParameter('subject_id')]
        #[WithoutValidation]
        public string $subjectId,
        #[Uuid]
        public string|null $scopeId = null,
    ) {}
}
