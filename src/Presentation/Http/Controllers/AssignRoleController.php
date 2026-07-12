<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Commands\AssignRole\AssignRole;
use NetCode\Access\Presentation\Http\Data\AssignRoleData;
use NetCode\Bus\Command\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final readonly class AssignRoleController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        AssignRoleData $data,
    ): JsonResponse {
        $this->bus->dispatch(new AssignRole(
            roleId: $data->roleId,
            subjectId: $data->subjectId,
            scopeId: $data->scopeId,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
