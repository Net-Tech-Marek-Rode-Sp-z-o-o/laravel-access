<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\GetRole;

use NetCode\Access\Application\Ports\RoleReadModel;
use NetCode\Access\Application\ReadModels\RoleView;
use NetCode\Bus\Query\QueryHandler;

final readonly class GetRoleHandler implements QueryHandler
{
    public function __construct(
        private RoleReadModel $roles,
    ) {}

    public function __invoke(
        GetRole $query,
    ): RoleView {
        return $this->roles->get($query->roleId);
    }
}
