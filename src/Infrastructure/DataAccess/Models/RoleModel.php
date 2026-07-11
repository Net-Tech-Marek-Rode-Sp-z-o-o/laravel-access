<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\DataAccess\Models;

use Illuminate\Database\Eloquent\Model;
use NetCode\Access\Domain\ValueObjects\RoleId;
use NetCode\Access\Infrastructure\DataAccess\Tables;
use NetCode\Domain\Laravel\IdentifierCast;

/**
 * @property RoleId $id
 * @property string $name
 * @property string $label
 */
final class RoleModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'id' => IdentifierCast::class.':'.RoleId::class,
    ];

    public function getTable(): string
    {
        return Tables::roles();
    }
}
