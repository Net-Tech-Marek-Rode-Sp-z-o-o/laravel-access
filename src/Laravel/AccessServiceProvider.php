<?php

declare(strict_types=1);

namespace NetCode\Access\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use NetCode\Access\Application\Port\Authorizer;
use NetCode\Access\Application\Port\CurrentSubject;
use NetCode\Access\Application\Port\RoleAssignments;
use NetCode\Access\Application\Port\RoleCatalog;
use NetCode\Access\Application\Port\ScopeContext;
use NetCode\Access\Domain\Contract\RoleAssignmentRepository;
use NetCode\Access\Domain\Contract\RoleRepository;
use NetCode\Access\Domain\Event\RoleAssigned;
use NetCode\Access\Domain\Event\RolePermissionsChanged;
use NetCode\Access\Domain\Event\RoleRevoked;
use NetCode\Access\Domain\Exception\RoleNameAlreadyTakenException;
use NetCode\Access\Domain\Exception\RoleNotFoundException;
use NetCode\Access\Infrastructure\Authorization\DatabaseAuthorizer;
use NetCode\Access\Infrastructure\Authorization\FlushResolvedPermissions;
use NetCode\Access\Infrastructure\Bus\BusRoleAssignments;
use NetCode\Access\Infrastructure\Bus\BusRoleCatalog;
use NetCode\Access\Infrastructure\DataAccess\Repositories\EloquentRoleAssignmentRepository;
use NetCode\Access\Infrastructure\DataAccess\Repositories\EloquentRoleRepository;
use NetCode\Access\Infrastructure\Scope\NullScopeContext;
use NetCode\Access\Infrastructure\Subject\NullCurrentSubject;
use NetCode\Access\Presentation\Http\Middleware\RequirePermission;
use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Kit\Clock;
use NetCode\Kit\SystemClock;

final class AccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/access.php', 'access');

        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(ScopeContext::class, NullScopeContext::class);
        $this->app->bind(CurrentSubject::class, NullCurrentSubject::class);
        $this->app->bind(RoleCatalog::class, BusRoleCatalog::class);
        $this->app->bind(RoleAssignments::class, BusRoleAssignments::class);
        $this->app->bind(RoleRepository::class, EloquentRoleRepository::class);
        $this->app->bind(RoleAssignmentRepository::class, EloquentRoleAssignmentRepository::class);

        $this->app->scoped(DatabaseAuthorizer::class);
        $this->app->bind(Authorizer::class, DatabaseAuthorizer::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->registerMiddlewareAlias();
        $this->registerCacheInvalidation();
        $this->registerExceptionRendering();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/access.php' => $this->app->configPath('access.php'),
            ], 'access-config');

            $this->publishes([
                __DIR__.'/../../database/migrations' => $this->app->databasePath('migrations'),
            ], 'access-migrations');
        }
    }

    private function registerMiddlewareAlias(): void
    {
        $alias = config('access.middleware_alias');

        if (! is_string($alias) || $alias === '') {
            return;
        }

        $this->app->make(Router::class)->aliasMiddleware($alias, RequirePermission::class);
    }

    private function registerCacheInvalidation(): void
    {
        $events = $this->app->make(Dispatcher::class);

        $listener = [FlushResolvedPermissions::class, '__invoke'];

        $events->listen(RoleAssigned::class, $listener);
        $events->listen(RoleRevoked::class, $listener);
        $events->listen(RolePermissionsChanged::class, $listener);
    }

    private function registerExceptionRendering(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(fn (RoleNotFoundException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 404));
        $handler->renderable(fn (RoleNameAlreadyTakenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 422));
        $handler->renderable(fn (InvalidArgumentException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], 422));
    }
}
