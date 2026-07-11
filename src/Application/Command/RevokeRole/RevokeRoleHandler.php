<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Command\RevokeRole;

use NetCode\Access\Domain\Contract\RoleAssignmentRepository;
use NetCode\Access\Domain\Contract\RoleRepository;
use NetCode\Access\Domain\ValueObjects\RoleName;
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
    ): null {
        $role = $this->roles->getByName(new RoleName($command->role));

        $assignment = $this->assignments->find(
            roleId: $role->id(),
            subjectId: new SubjectId($command->subjectId),
            scopeId: ScopeId::fromNullable($command->scopeId),
        );

        if ($assignment === null) {
            return null;
        }

        $assignment->revoke($this->clock->now());

        $this->assignments->remove($assignment);

        return null;
    }
}
