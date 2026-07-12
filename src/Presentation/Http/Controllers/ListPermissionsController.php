<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Queries\ListPermissions\ListPermissions;
use NetCode\Access\Presentation\Http\Resources\PermissionsResource;
use NetCode\Bus\Query\QueryBus;

final readonly class ListPermissionsController
{
    public function __construct(
        private QueryBus $queries,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new PermissionsResource($this->queries->ask(new ListPermissions))->response();
    }
}
