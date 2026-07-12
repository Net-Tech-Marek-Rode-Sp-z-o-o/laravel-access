<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Data;

use NetCode\Access\Domain\ValueObjects\PermissionId;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithoutValidation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class SetRolePermissionsData extends Data
{
    /** @param list<string> $permissions */
    public function __construct(
        #[FromRouteParameter('roleId')]
        #[WithoutValidation]
        public string $roleId,
        public array $permissions,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'max:'.PermissionId::MAX_LENGTH],
        ];
    }
}
