<?php

declare(strict_types=1);

namespace NetCode\Access\Laravel;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use NetCode\Access\Application\Ports\Authorizer;
use NetCode\Access\Application\Ports\CurrentSubject;
use NetCode\Access\Application\Ports\RoleAssignments;
use NetCode\Access\Application\Ports\RoleCatalog;
use NetCode\Access\Application\Ports\RoleReadModel;
use NetCode\Access\Application\Ports\ScopeContext;
use NetCode\Access\Domain\Contracts\RoleAssignmentRepository;
use NetCode\Access\Domain\Contracts\RoleRepository;
use NetCode\Access\Domain\Events\RoleAssigned;
use NetCode\Access\Domain\Events\RolePermissionsChanged;
use NetCode\Access\Domain\Events\RoleRevoked;
use NetCode\Access\Domain\Exceptions\RoleNameAlreadyTakenException;
use NetCode\Access\Domain\Exceptions\RoleNotFoundException;
use NetCode\Access\Infrastructure\Authorization\DatabaseAuthorizer;
use NetCode\Access\Infrastructure\Authorization\FlushResolvedPermissions;
use NetCode\Access\Infrastructure\Bus\BusRoleAssignments;
use NetCode\Access\Infrastructure\Bus\BusRoleCatalog;
use NetCode\Access\Infrastructure\DataAccess\ReadModels\DatabaseRoleReadModel;
use NetCode\Access\Infrastructure\DataAccess\Repositories\EloquentRoleAssignmentRepository;
use NetCode\Access\Infrastructure\DataAccess\Repositories\EloquentRoleRepository;
use NetCode\Access\Infrastructure\Scope\NullScopeContext;
use NetCode\Access\Infrastructure\Subject\NullCurrentSubject;
use NetCode\Access\Presentation\Http\Middleware\RequirePermission;
use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Kit\Clock;
use NetCode\Kit\SystemClock;
use Symfony\Component\HttpFoundation\Response;

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
        $this->app->bind(RoleReadModel::class, DatabaseRoleReadModel::class);

        $this->app->scoped(DatabaseAuthorizer::class);
        $this->app->bind(Authorizer::class, DatabaseAuthorizer::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->registerMiddlewareAlias();
        $this->registerRoutes();
        $this->registerGate();
        $this->registerCacheInvalidation();
        $this->registerExceptionRendering();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/access.php' => $this->app->configPath('access.php'),
            ], 'access-config');

            $this->publishes([
                __DIR__.'/../../database/migrations' => $this->app->databasePath('migrations'),
            ], 'access-migrations');

            $this->publishes([
                __DIR__.'/../../routes/api.php' => $this->app->basePath('routes/access.php'),
            ], 'access-routes');
        }
    }

    private function registerRoutes(): void
    {
        if (config('access.routes') !== true) {
            return;
        }

        $prefix = config('access.route_prefix');

        Route::prefix(is_string($prefix) ? $prefix : '')
            ->middleware($this->routeMiddleware())
            ->group(__DIR__.'/../../routes/api.php');
    }

    /** @return list<string> */
    private function routeMiddleware(): array
    {
        $permission = config('access.admin_permission');

        if (! is_string($permission) || $permission === '') {
            return ['api'];
        }

        return ['api', RequirePermission::class.':'.$permission];
    }

    private function registerMiddlewareAlias(): void
    {
        $alias = config('access.middleware_alias');

        if (! is_string($alias) || $alias === '') {
            return;
        }

        $this->app->make(Router::class)->aliasMiddleware($alias, RequirePermission::class);
    }

    private function registerGate(): void
    {
        if (config('access.gate') !== true) {
            return;
        }

        Gate::before(function (Authenticatable $user, string $ability): bool|null {
            $subjectId = (string) $user->getAuthIdentifier();

            try {
                $granted = $this->app->make(Authorizer::class)->can(
                    $subjectId,
                    $ability,
                    $this->app->make(ScopeContext::class)->current(),
                );
            } catch (InvalidArgumentException) {
                return null;
            }

            // null, not false: abstain so Policies still get their say on abilities we do not grant.
            return $granted ? true : null;
        });
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

        $handler->renderable(fn (RoleNotFoundException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND));
        $handler->renderable(fn (RoleNameAlreadyTakenException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        $handler->renderable(fn (InvalidArgumentException $e): JsonResponse => new JsonResponse(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
