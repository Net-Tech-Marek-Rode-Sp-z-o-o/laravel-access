<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Queries\GetRoleByName;

use NetCode\Access\Application\Dto\RoleView;
use NetCode\Access\Application\Ports\RoleReadModel;
use NetCode\Bus\Query\QueryHandler;

final readonly class GetRoleByNameHandler implements QueryHandler
{
    public function __construct(
        private RoleReadModel $roles,
    ) {}

    public function __invoke(
        GetRoleByName $query,
    ): RoleView {
        return $this->roles->getByName($query->name);
    }
}
