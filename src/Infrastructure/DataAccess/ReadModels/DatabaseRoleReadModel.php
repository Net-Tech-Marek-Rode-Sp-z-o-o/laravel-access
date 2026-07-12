<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\DataAccess\ReadModels;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use NetCode\Access\Application\Ports\RoleReadModel;
use NetCode\Access\Application\ReadModels\RoleView;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Infrastructure\DataAccess\Tables;

final readonly class DatabaseRoleReadModel implements RoleReadModel
{
    public function __construct(
        private DatabaseManager $database,
    ) {}

    /** @return list<RoleView> */
    public function all(): array
    {
        return $this->views($this->query());
    }

    public function get(string $roleId): RoleView
    {
        $id = RoleId::fromString($roleId);

        $views = $this->views($this->query()->where('r.id', $id->value()));

        return $views[0] ?? throw RoleNotFoundException::withId($id);
    }

    private function query(): Builder
    {
        return $this->database->connection()
            ->table(Tables::roles(), 'r')
            ->leftJoin(Tables::rolePermission().' as rp', 'rp.role_id', '=', 'r.id')
            ->orderBy('r.name')
            ->orderBy('rp.permission');
    }

    /** @return list<RoleView> */
    private function views(Builder $query): array
    {
        $roles = [];

        foreach ($query->get(['r.id', 'r.name', 'r.label', 'rp.permission']) as $row) {
            $id = (string) $row->id;

            $roles[$id] ??= [
                'name' => (string) $row->name,
                'label' => (string) $row->label,
                'permissions' => [],
            ];

            if ($row->permission !== null) {
                $roles[$id]['permissions'][] = (string) $row->permission;
            }
        }

        return array_values(array_map(
            static fn (string $id, array $role): RoleView => new RoleView(
                id: $id,
                name: $role['name'],
                label: $role['label'],
                permissions: $role['permissions'],
            ),
            array_keys($roles),
            $roles,
        ));
    }
}
