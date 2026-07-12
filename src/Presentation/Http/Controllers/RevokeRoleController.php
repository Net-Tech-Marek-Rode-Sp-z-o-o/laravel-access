<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Commands\RevokeRole\RevokeRole;
use NetCode\Access\Presentation\Http\Data\RevokeRoleData;
use NetCode\Bus\Command\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final readonly class RevokeRoleController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        RevokeRoleData $data,
    ): JsonResponse {
        $this->bus->dispatch(new RevokeRole(
            roleId: $data->roleId,
            subjectId: $data->subjectId,
            scopeId: $data->scopeId,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
