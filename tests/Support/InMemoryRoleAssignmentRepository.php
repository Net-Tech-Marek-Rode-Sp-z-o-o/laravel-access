<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Support;

use NetCode\Access\Domain\Contract\RoleAssignmentRepository;
use NetCode\Access\Domain\RoleAssignment;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Domain\DomainEvent;

final class InMemoryRoleAssignmentRepository implements RoleAssignmentRepository
{
    /** @var array<string, RoleAssignment> */
    public array $assignments = [];

    /** @var list<DomainEvent> */
    public array $published = [];

    public function __construct(RoleAssignment ...$assignments)
    {
        foreach ($assignments as $assignment) {
            $this->assignments[$this->key($assignment->roleId(), $assignment->subjectId(), $assignment->scopeId())] = $assignment;
        }
    }

    public function find(RoleId $roleId, SubjectId $subjectId, ScopeId|null $scopeId): RoleAssignment|null
    {
        return $this->assignments[$this->key($roleId, $subjectId, $scopeId)] ?? null;
    }

    public function save(RoleAssignment $assignment): void
    {
        $key = $this->key($assignment->roleId(), $assignment->subjectId(), $assignment->scopeId());

        $this->assignments[$key] = $assignment;

        $this->published = [...$this->published, ...$assignment->releaseEvents()];
    }

    public function remove(RoleAssignment $assignment): void
    {
        $key = $this->key($assignment->roleId(), $assignment->subjectId(), $assignment->scopeId());

        unset($this->assignments[$key]);

        $this->published = [...$this->published, ...$assignment->releaseEvents()];
    }

    private function key(RoleId $roleId, SubjectId $subjectId, ScopeId|null $scopeId): string
    {
        return implode('|', [$roleId->value(), $subjectId->value(), $scopeId?->value() ?? '*']);
    }
}
