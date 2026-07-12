<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CreatedRoleResource extends JsonResource
{
    public function __construct(
        private readonly string $id,
    ) {
        parent::__construct($id);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
