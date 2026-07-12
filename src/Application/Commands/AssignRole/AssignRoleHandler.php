<?php

declare(strict_types=1);

namespace NetCode\Access\Application\Commands\AssignRole;

use NetCode\Access\Domain\Contracts\RoleAssignmentRepository;
use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\RoleAssignment;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Bus\Command\CommandHandler;
use NetCode\Kit\Clock;

final readonly class AssignRoleHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RoleRepository $roles,
        private RoleAssignmentRepository $assignments,
    ) {}

    public function __invoke(
        AssignRole $command,
    ): null {
        $role = $this->roles->getByName(new RoleName($command->role));
        $subjectId = new SubjectId($command->subjectId);
        $scopeId = ScopeId::fromNullable($command->scopeId);

        if ($this->assignments->find($role->id(), $subjectId, $scopeId) !== null) {
            return null;
        }

        $this->assignments->save(RoleAssignment::grant(
            roleId: $role->id(),
            subjectId: $subjectId,
            scopeId: $scopeId,
            now: $this->clock->now(),
        ));

        return null;
    }
}
