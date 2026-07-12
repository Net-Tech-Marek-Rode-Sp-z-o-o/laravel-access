<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\Authorization;

use BackedEnum;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use NetCode\Access\Application\Ports\Authorizer;
use NetCode\Access\Domain\ValueObjects\PermissionId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Access\Infrastructure\DataAccess\Tables;

final class DatabaseAuthorizer implements Authorizer
{
    private const string GLOBAL_SCOPE = '*';

    /** @var array<string, array{roles: list<string>, permissions: list<string>}> */
    private array $resolved = [];

    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    public function can(string $subjectId, string|BackedEnum $permission, string|null $scopeId = null): bool
    {
        $granted = PermissionId::from($permission)->value();

        return in_array($granted, $this->resolve($subjectId, $scopeId)['permissions'], strict: true);
    }

    public function hasRole(string $subjectId, string|BackedEnum $role, string|null $scopeId = null): bool
    {
        $name = RoleName::from($role)->value();

        return in_array($name, $this->resolve($subjectId, $scopeId)['roles'], strict: true);
    }

    /** @return list<string> */
    public function rolesOf(string $subjectId, string|null $scopeId = null): array
    {
        return $this->resolve($subjectId, $scopeId)['roles'];
    }

    /** @return list<string> */
    public function permissionsOf(string $subjectId, string|null $scopeId = null): array
    {
        return $this->resolve($subjectId, $scopeId)['permissions'];
    }

    public function flush(string|null $subjectId = null): void
    {
        if ($subjectId === null) {
            $this->resolved = [];

            return;
        }

        $prefix = new SubjectId($subjectId)->value().'|';

        foreach (array_keys($this->resolved) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->resolved[$key]);
            }
        }
    }

    /** @return array{roles: list<string>, permissions: list<string>} */
    private function resolve(string $subjectId, string|null $scopeId): array
    {
        $subject = new SubjectId($subjectId)->value();
        $scope = ScopeId::fromNullable($scopeId)?->value();
        $key = $subject.'|'.($scope ?? self::GLOBAL_SCOPE);

        return $this->resolved[$key] ??= $this->query($subject, $scope);
    }

    /** @return array{roles: list<string>, permissions: list<string>} */
    private function query(string $subject, string|null $scope): array
    {
        $rows = $this->database->connection()
            ->table(Tables::roleUser(), 'ru')
            ->join(Tables::roles().' as r', 'r.id', '=', 'ru.role_id')
            ->leftJoin(Tables::rolePermission().' as rp', 'rp.role_id', '=', 'r.id')
            ->where('ru.user_id', $subject)
            ->where(static function (Builder $query) use ($scope): void {
                $query->whereNull('ru.scope_id');

                if ($scope !== null) {
                    $query->orWhere('ru.scope_id', $scope);
                }
            })
            ->get(['r.name as role_name', 'rp.permission as permission']);

        $roles = [];
        $permissions = [];

        foreach ($rows as $row) {
            $roles[(string) $row->role_name] = true;

            if ($row->permission !== null) {
                $permissions[(string) $row->permission] = true;
            }
        }

        return [
            'roles' => $this->sorted($roles),
            'permissions' => $this->sorted($permissions),
        ];
    }

    /**
     * @param array<string, true> $set
     * @return list<string>
     */
    private function sorted(array $set): array
    {
        $values = array_keys($set);
        sort($values);

        return $values;
    }
}
