<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Unit\Domain;

use NetCode\Access\Domain\ValueObjects\PermissionId;
use NetCode\Access\Domain\ValueObjects\PermissionSet;
use NetCode\Access\Domain\ValueObjects\RoleName;
use NetCode\Access\Domain\ValueObjects\ScopeId;
use NetCode\Access\Domain\ValueObjects\SubjectId;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Domain\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    #[Test]
    public function a_permission_id_is_built_from_a_string_or_a_backed_enum(): void
    {
        $this->assertSame('invoices.issue', PermissionId::from('invoices.issue')->value());
        $this->assertSame('invoices.issue', PermissionId::from(Permission::InvoicesIssue)->value());
        $this->assertSame('invoices.issue', PermissionId::from(new PermissionId('invoices.issue'))->value());
    }

    /** @return list<array{string}> */
    public static function invalidPermissions(): array
    {
        return [[''], ['   '], [str_repeat('a', PermissionId::MAX_LENGTH + 1)]];
    }

    #[Test]
    #[DataProvider('invalidPermissions')]
    public function an_invalid_permission_id_is_rejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PermissionId($value);
    }

    #[Test]
    public function a_role_name_is_normalised_to_lower_case(): void
    {
        $this->assertSame('global-admin', new RoleName(' Global-Admin ')->value());
        $this->assertSame('manager', RoleName::from(Role::Manager)->value());
    }

    /** @return list<array{string}> */
    public static function invalidRoleNames(): array
    {
        return [[''], ['global admin'], ['-admin'], ['admin-'], ['admin!'], [str_repeat('a', RoleName::MAX_LENGTH + 1)]];
    }

    #[Test]
    #[DataProvider('invalidRoleNames')]
    public function an_invalid_role_name_is_rejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RoleName($value);
    }

    #[Test]
    public function subject_and_scope_ids_are_uuids(): void
    {
        $this->assertSame(Ids::SUBJECT, new SubjectId(Ids::SUBJECT)->value());
        $this->assertSame(Ids::STORE_A, ScopeId::fromString(Ids::STORE_A)->value());
        $this->assertNull(ScopeId::fromNullable(null));
        $this->assertSame(Ids::STORE_A, ScopeId::fromNullable(Ids::STORE_A)?->value());
        $this->assertTrue(new SubjectId(Ids::SUBJECT)->equals(new SubjectId(Ids::SUBJECT)));
        $this->assertFalse(new SubjectId(Ids::SUBJECT)->equals(new SubjectId(Ids::OTHER_SUBJECT)));
    }

    /** @return list<array{string}> */
    public static function invalidUuids(): array
    {
        return [[''], ['user-1'], ['not-a-uuid'], ['0193f0a0-0000-7000-8000-00000000000']];
    }

    #[Test]
    #[DataProvider('invalidUuids')]
    public function a_subject_id_that_is_not_a_uuid_is_rejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SubjectId($value);
    }

    #[Test]
    #[DataProvider('invalidUuids')]
    public function a_scope_id_that_is_not_a_uuid_is_rejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ScopeId($value);
    }

    #[Test]
    public function a_permission_set_deduplicates_sorts_and_compares_by_value(): void
    {
        $set = PermissionSet::from(['users.invite', 'invoices.issue', 'users.invite']);

        $this->assertSame(['invoices.issue', 'users.invite'], $set->toStrings());
        $this->assertCount(2, $set);
        $this->assertTrue($set->contains(new PermissionId('users.invite')));
        $this->assertFalse($set->contains(new PermissionId('users.remove')));
        $this->assertTrue($set->equals(PermissionSet::from([Permission::UsersInvite, Permission::InvoicesIssue])));
        $this->assertFalse($set->equals(PermissionSet::empty()));
        $this->assertTrue(PermissionSet::empty()->isEmpty());
    }
}
