<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Access\Application\Port\RoleAssignments;
use NetCode\Access\Application\Port\RoleCatalog;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GateDisabledTest extends TestCase
{
    use RefreshDatabase;

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('access.gate', false);
    }

    #[Test]
    public function the_gate_is_left_alone_when_the_integration_is_disabled(): void
    {
        $this->app->make(RoleCatalog::class)->create(
            name: Role::Manager,
            label: 'Manager',
            permissions: [Permission::InvoicesIssue],
        );

        $this->app->make(RoleAssignments::class)->assign(
            subjectId: Ids::SUBJECT,
            role: Role::Manager,
        );

        $gate = $this->app->make(Gate::class)->forUser(new GenericUser(['id' => Ids::SUBJECT]));

        $this->assertFalse($gate->allows('invoices.issue'));
    }
}
