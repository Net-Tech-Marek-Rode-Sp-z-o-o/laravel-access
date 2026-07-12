<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Commands\DeleteRole\DeleteRole;
use NetCode\Access\Presentation\Http\Data\DeleteRoleData;
use NetCode\Bus\Command\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteRoleController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        DeleteRoleData $data,
    ): JsonResponse {
        $this->bus->dispatch(new DeleteRole(
            roleId: $data->roleId,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
