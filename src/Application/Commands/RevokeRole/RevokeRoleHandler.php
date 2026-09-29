<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Commands\RevokeRole;

use NetCode\Access\Domain\Contracts\RoleAssignmentRepository;
use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Bus\Command\CommandHandler;
use NetCode\Kit\Clock;

final readonly class RevokeRoleHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RoleRepository $roles,
        private RoleAssignmentRepository $assignments,
    ) {}

    public function __invoke(
        RevokeRole $command,
    ): void {
        $role = $this->roles->getById(RoleId::fromString($command->roleId));

        $assignment = $this->assignments->find(
            roleId: $role->id(),
            subjectId: new SubjectId($command->subjectId),
            scopeId: ScopeId::fromNullable($command->scopeId),
        );

        if ($assignment === null) {
            return;
        }

        $assignment->revoke($this->clock->now());

        $this->assignments->remove($assignment);
    }
}
