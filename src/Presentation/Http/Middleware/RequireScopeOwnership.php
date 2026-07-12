<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NetCode\Access\Application\Ports\Authorizer;
use NetCode\Access\Application\Ports\CurrentSubject;
use NetCode\Access\Application\Ports\ScopeContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RequireScopeOwnership
{
    public function __construct(
        private Authorizer $authorizer,
        private ScopeContext $scope,
        private CurrentSubject $subject,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $permission,
    ): Response {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $subjectId = $this->subject->id();

        if ($subjectId === null) {
            throw new UnauthorizedHttpException('Bearer', 'Unauthenticated.');
        }

        // A global grant of the permission is what makes a platform admin; anyone else only
        // holds it inside a scope, so they may not write outside that scope — the role
        // catalogue included, since roles are global and carry no scope_id.
        if ($this->authorizer->can($subjectId, $permission)) {
            return $next($request);
        }

        $current = $this->scope->current();
        $target = $request->input('scope_id');

        if ($current === null || $target !== $current) {
            throw new AccessDeniedHttpException('This action is unauthorized outside the current scope.');
        }

        return $next($request);
    }
}
