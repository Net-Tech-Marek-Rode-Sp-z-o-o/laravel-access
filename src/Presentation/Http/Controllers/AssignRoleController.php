<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Commands\AssignRole\AssignRole;
use NetCode\Access\Application\Queries\GetRole\GetRole;
use NetCode\Access\Presentation\Http\Data\AssignRoleData;
use NetCode\Bus\Command\CommandBus;
use NetCode\Bus\Query\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final readonly class AssignRoleController
{
    public function __construct(
        private CommandBus $bus,
        private QueryBus $queries,
    ) {}

    public function __invoke(
        AssignRoleData $data,
    ): JsonResponse {
        $role = $this->queries->ask(new GetRole(
            roleId: $data->roleId,
        ));

        $this->bus->dispatch(new AssignRole(
            role: $role->name,
            subjectId: $data->subjectId,
            scopeId: $data->scopeId,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
