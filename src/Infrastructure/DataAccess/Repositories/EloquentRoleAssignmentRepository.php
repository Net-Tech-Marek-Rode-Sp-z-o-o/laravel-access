<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\DataAccess\Repositories;

use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use NetCode\Access\Domain\Contracts\RoleAssignmentRepository;
use NetCode\Access\Domain\RoleAssignment;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Access\Infrastructure\DataAccess\Tables;
use NetCode\Domain\DomainEventPublisher;

final readonly class EloquentRoleAssignmentRepository implements RoleAssignmentRepository
{
    public function __construct(
        private DatabaseManager $database,
        private DomainEventPublisher $events,
    ) {}

    public function find(RoleId $roleId, SubjectId $subjectId, ScopeId|null $scopeId): RoleAssignment|null
    {
        $row = $this->match($roleId, $subjectId, $scopeId)->first();

        if ($row === null) {
            return null;
        }

        return RoleAssignment::reconstitute(
            roleId: $roleId,
            subjectId: $subjectId,
            scopeId: $scopeId,
            grantedAt: new DateTimeImmutable((string) $row->granted_at),
        );
    }

    public function save(RoleAssignment $assignment): void
    {
        $this->database->connection()->table(Tables::roleUser())->insert([
            'role_id' => $assignment->roleId()->value(),
            'user_id' => $assignment->subjectId()->value(),
            'scope_id' => $assignment->scopeId()?->value(),
            'granted_at' => $assignment->grantedAt()->format('Y-m-d H:i:s'),
        ]);

        $this->events->publish(...$assignment->releaseEvents());
    }

    public function remove(RoleAssignment $assignment): void
    {
        $this->match(
            roleId: $assignment->roleId(),
            subjectId: $assignment->subjectId(),
            scopeId: $assignment->scopeId(),
        )->delete();

        $this->events->publish(...$assignment->releaseEvents());
    }

    private function match(RoleId $roleId, SubjectId $subjectId, ScopeId|null $scopeId): Builder
    {
        $query = $this->database->connection()
            ->table(Tables::roleUser())
            ->where('role_id', $roleId->value())
            ->where('user_id', $subjectId->value());

        return $scopeId === null
            ? $query->whereNull('scope_id')
            : $query->where('scope_id', $scopeId->value());
    }
}
