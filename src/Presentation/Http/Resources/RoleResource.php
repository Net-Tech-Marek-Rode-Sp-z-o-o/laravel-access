<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Access\Application\ReadModels\RoleView;

final class RoleResource extends JsonResource
{
    public function __construct(
        private readonly RoleView $role,
    ) {
        parent::__construct($role);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            'label' => $this->role->label,
            'permissions' => $this->role->permissions,
        ];
    }
}
