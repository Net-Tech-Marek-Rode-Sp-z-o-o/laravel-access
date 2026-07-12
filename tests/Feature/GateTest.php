<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate as GateFacade;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Application\Ports\RoleCatalog;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class GateTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $id): Authenticatable
    {
        return new GenericUser(['id' => $id]);
    }

    private function gate(Authenticatable $user): Gate
    {
        return $this->app->make(Gate::class)->forUser($user);
    }

    private function grantManagerTo(string $subjectId, string|null $scopeId): void
    {
        $this->app->make(RoleCatalog::class)->create(
            name: Role::Manager,
            label: 'Manager',
            permissions: [Permission::InvoicesIssue],
        );

        $this->app->make(RoleAssignments::class)->assign(
            subjectId: $subjectId,
            role: Role::Manager,
            scopeId: $scopeId,
        );
    }

    #[Test]
    public function a_granted_permission_passes_the_gate(): void
    {
        $this->grantManagerTo(Ids::SUBJECT, null);

        $this->assertTrue($this->gate($this->user(Ids::SUBJECT))->allows('invoices.issue'));
    }

    #[Test]
    public function an_ungranted_permission_is_denied_by_the_gate(): void
    {
        $this->grantManagerTo(Ids::SUBJECT, null);

        $this->assertFalse($this->gate($this->user(Ids::SUBJECT))->allows('users.invite'));
        $this->assertFalse($this->gate($this->user(Ids::OTHER_SUBJECT))->allows('invoices.issue'));
    }

    #[Test]
    public function the_gate_abstains_so_a_defined_ability_still_decides(): void
    {
        $this->grantManagerTo(Ids::SUBJECT, null);

        GateFacade::define('edit-post', static fn (): bool => true);

        $this->assertTrue($this->gate($this->user(Ids::SUBJECT))->allows('edit-post'));
    }

    #[Test]
    public function a_subject_id_that_is_not_a_uuid_makes_the_gate_abstain(): void
    {
        GateFacade::define('edit-post', static fn (): bool => true);

        $gate = $this->gate($this->user('not-a-uuid'));

        $this->assertTrue($gate->allows('edit-post'));
        $this->assertFalse($gate->allows('invoices.issue'));
    }
}
