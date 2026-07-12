<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Access\Application\Commands\CreateRole\CreateRole;
use NetCode\Access\Presentation\Http\Data\CreateRoleData;
use NetCode\Access\Presentation\Http\Resources\CreatedRoleResource;
use NetCode\Bus\Command\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final readonly class CreateRoleController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        CreateRoleData $data,
    ): JsonResponse {
        $roleId = $this->bus->dispatch(new CreateRole(
            name: $data->name,
            label: $data->label,
        ));

        return new CreatedRoleResource($roleId)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
