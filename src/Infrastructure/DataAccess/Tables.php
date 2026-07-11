<?php

declare(strict_types=1);

namespace NetCode\Access\Infrastructure\DataAccess;

final class Tables
{
    public const string DEFAULT_PREFIX = 'access_';

    public static function prefix(): string
    {
        $prefix = config('access.table_prefix', self::DEFAULT_PREFIX);

        return is_string($prefix) ? $prefix : self::DEFAULT_PREFIX;
    }

    public static function roles(): string
    {
        return self::prefix().'roles';
    }

    public static function rolePermission(): string
    {
        return self::prefix().'role_permission';
    }

    public static function roleUser(): string
    {
        return self::prefix().'role_user';
    }
}
