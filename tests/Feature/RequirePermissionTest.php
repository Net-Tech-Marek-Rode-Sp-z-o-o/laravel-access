<?php

declare(strict_types=1);

namespace NetCode\Access\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use NetCode\Access\Application\Port\CurrentSubject;
use NetCode\Access\Application\Port\RoleAssignments;
use NetCode\Access\Application\Port\RoleCatalog;
use NetCode\Access\Application\Port\ScopeContext;
use NetCode\Access\Tests\Support\Permission;
use NetCode\Access\Tests\Support\Role;
use NetCode\Access\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class RequirePermissionTest extends TestCase
{
    use RefreshDatabase;

    private string|null $subjectId = null;

    private string|null $scopeId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(CurrentSubject::class, new class($this) implements CurrentSubject
        {
            public function __construct(
                private readonly RequirePermissionTest $test,
            ) {}

            public function id(): string|null
            {
                return $this->test->subjectId();
            }
        });

        $this->app->instance(ScopeContext::class, new class($this) implements ScopeContext
        {
            public function __construct(
                private readonly RequirePermissionTest $test,
            ) {}

            public function current(): string|null
            {
                return $this->test->scopeId();
            }
        });
    }

    /** @param Router $router */
    protected function defineRoutes($router): void
    {
        $router->middleware('permission:invoices.issue')->get(
            '/invoices',
            static fn (): array => ['ok' => true],
        );
    }

    public function subjectId(): string|null
    {
        return $this->subjectId;
    }

    public function scopeId(): string|null
    {
        return $this->scopeId;
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
        $this->subjectId = 'user-1';
        $this->scopeId = 'store-a';
        $this->grantManagerTo('user-1', 'store-a');

        $this->getJson('/invoices')->assertOk()->assertJsonPath('ok', true);
    }

    #[Test]
    public function a_subject_without_the_permission_is_forbidden(): void
    {
        $this->subjectId = 'user-1';
        $this->scopeId = 'store-a';

        $this->getJson('/invoices')->assertForbidden();
    }

    #[Test]
    public function a_scoped_permission_does_not_leak_into_another_scope(): void
    {
        $this->subjectId = 'user-1';
        $this->grantManagerTo('user-1', 'store-a');

        $this->scopeId = 'store-b';
        $this->getJson('/invoices')->assertForbidden();

        $this->scopeId = 'store-a';
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
