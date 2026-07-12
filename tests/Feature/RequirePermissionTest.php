<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use NetCode\Access\Application\Ports\CurrentSubject;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Application\Ports\RoleCatalog;
use NetCode\Access\Application\Ports\ScopeContext;
use NetCode\Access\Tests\Support\FakeCurrentSubject;
use NetCode\Access\Tests\Support\FakeScopeContext;
use NetCode\Access\Tests\Support\Ids;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class RequirePermissionTest extends TestCase
{
    use RefreshDatabase;

    private FakeCurrentSubject $subject;

    private FakeScopeContext $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new FakeCurrentSubject;
        $this->scope = new FakeScopeContext;

        $this->app->instance(CurrentSubject::class, $this->subject);
        $this->app->instance(ScopeContext::class, $this->scope);
    }

    /** @param Router $router */
    protected function defineRoutes($router): void
    {
        $router->middleware('permission:invoices.issue')->get(
            '/invoices',
            static fn (): array => ['ok' => true],
        );
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
    public function the_middleware_alias_is_registered(): void
    {
        $this->assertArrayHasKey('permission', $this->app->make(Router::class)->getMiddleware());
    }

    #[Test]
    public function a_subject_holding_the_permission_is_allowed_through(): void
    {
        $this->subject->becomes(Ids::SUBJECT);
        $this->scope->enters(Ids::STORE_A);
        $this->grantManagerTo(Ids::SUBJECT, Ids::STORE_A);

        $this->getJson('/invoices')->assertOk()->assertJsonPath('ok', true);
    }

    #[Test]
    public function a_subject_without_the_permission_is_forbidden(): void
    {
        $this->subject->becomes(Ids::SUBJECT);
        $this->scope->enters(Ids::STORE_A);

        $this->getJson('/invoices')->assertForbidden();
    }

    #[Test]
    public function a_scoped_permission_does_not_leak_into_another_scope(): void
    {
        $this->subject->becomes(Ids::SUBJECT);
        $this->grantManagerTo(Ids::SUBJECT, Ids::STORE_A);

        $this->scope->enters(Ids::STORE_B);
        $this->getJson('/invoices')->assertForbidden();

        $this->scope->enters(Ids::STORE_A);
        $this->getJson('/invoices')->assertOk();
    }

    #[Test]
    public function an_anonymous_request_is_unauthenticated(): void
    {
        $this->getJson('/invoices')->assertUnauthorized();
    }

    #[Test]
    public function routes_without_the_middleware_stay_open(): void
    {
        Route::get('/public', static fn (): array => ['ok' => true]);

        $this->getJson('/public')->assertOk();
    }
}
