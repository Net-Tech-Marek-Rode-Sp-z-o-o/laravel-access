<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\DataAccess\Repositories;

use Illuminate\Database\DatabaseManager;
use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\Role;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Infrastructure\DataAccess\Mappers\RoleMapper;
use NetCode\Access\Infrastructure\DataAccess\Models\RoleModel;
use NetCode\Access\Infrastructure\DataAccess\Tables;
use NetCode\Domain\DomainEventPublisher;

final readonly class EloquentRoleRepository implements RoleRepository
{
    public function __construct(
        private RoleMapper $mapper,
        private DatabaseManager $database,
        private DomainEventPublisher $events,
    ) {}

    public function nextId(): RoleId
    {
        return RoleId::random();
    }

    public function findByName(RoleName $name): Role|null
    {
        $model = RoleModel::query()->where('name', $name->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function getById(RoleId $id): Role
    {
        $model = RoleModel::query()->find($id->value());

        return $model === null
            ? throw RoleNotFoundException::withId($id)
            : $this->toDomain($model);
    }

    public function getByName(RoleName $name): Role
    {
        return $this->findByName($name) ?? throw RoleNotFoundException::withName($name);
    }

    public function save(Role $role): void
    {
        $model = RoleModel::query()->findOrNew($role->id()->value());
        $this->mapper->hydrate($role, $model);
        $model->save();

        $this->syncPermissions($role);

        $this->events->publish(...$role->releaseEvents());
    }

    public function delete(Role $role): void
    {
        RoleModel::query()->whereKey($role->id()->value())->delete();

        $this->events->publish(...$role->releaseEvents());
    }

    private function toDomain(RoleModel $model): Role
    {
        return $this->mapper->toDomain($model, $this->permissionsOf($model->id));
    }

    private function permissionsOf(RoleId $id): PermissionSet
    {
        /** @var list<string> $permissions */
        $permissions = $this->database->connection()
            ->table(Tables::rolePermission())
            ->where('role_id', $id->value())
            ->pluck('permission')
            ->all();

        return PermissionSet::from($permissions);
    }

    private function syncPermissions(Role $role): void
    {
        $connection = $this->database->connection();

        $connection->table(Tables::rolePermission())
            ->where('role_id', $role->id()->value())
            ->delete();

        if ($role->permissions()->isEmpty()) {
            return;
        }

        $connection->table(Tables::rolePermission())->insert(array_map(
            static fn (string $permission): array => [
                'role_id' => $role->id()->value(),
                'permission' => $permission,
            ],
            $role->permissions()->toStrings(),
        ));
    }
}
