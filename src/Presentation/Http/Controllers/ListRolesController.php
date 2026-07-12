<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Queries\ListRoles\ListRoles;
use NetCode\Access\Presentation\Http\Resources\RoleResource;
use NetCode\Bus\Query\QueryBus;

final readonly class ListRolesController
{
    public function __construct(
        private QueryBus $queries,
    ) {}

    public function __invoke(): JsonResponse
    {
        return RoleResource::collection($this->queries->ask(new ListRoles))->response();
    }
}
