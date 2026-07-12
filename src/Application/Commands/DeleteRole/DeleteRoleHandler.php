<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Commands\DeleteRole;

use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Bus\Command\CommandHandler;

final readonly class DeleteRoleHandler implements CommandHandler
{
    public function __construct(
        private RoleRepository $roles,
    ) {}

    public function __invoke(
        DeleteRole $command,
    ): null {
        $this->roles->delete($this->roles->getById(RoleId::fromString($command->roleId)));

        return null;
    }
}
