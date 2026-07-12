<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Commands\SetRolePermissions\SetRolePermissions;
use NetCode\Access\Presentation\Http\Data\SetRolePermissionsData;
use NetCode\Bus\Command\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetRolePermissionsController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        SetRolePermissionsData $data,
    ): JsonResponse {
        $this->bus->dispatch(new SetRolePermissions(
            roleId: $data->roleId,
            permissions: $data->permissions,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
