<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PermissionsResource extends JsonResource
{
    /** @param list<string> $permissions */
    public function __construct(
        private readonly array $permissions,
    ) {
        parent::__construct($permissions);
    }

    /** @return list<string> */
    public function toArray(Request $request): array
    {
        return $this->permissions;
    }
}
