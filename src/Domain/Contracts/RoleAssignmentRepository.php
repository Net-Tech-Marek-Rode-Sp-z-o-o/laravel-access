<?php

declare(strict_types=1);

namespace NetCode\Access\Domain\Contracts;

use NetCode\Access\Domain\RoleAssignment;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;

interface RoleAssignmentRepository
{
    public function find(RoleId $roleId, SubjectId $subjectId, ScopeId|null $scopeId): RoleAssignment|null;

    public function save(RoleAssignment $assignment): void;

    public function remove(RoleAssignment $assignment): void;
}
