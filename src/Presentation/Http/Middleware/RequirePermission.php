<?php

declare(strict_types=1);

namespace NetCode\Access\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NetCode\Access\Application\Port\Authorizer;
use NetCode\Access\Application\Port\CurrentSubject;
use NetCode\Access\Application\Port\ScopeContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RequirePermission
{
    public function __construct(
        private Authorizer $authorizer,
        private ScopeContext $scope,
        private CurrentSubject $subject,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions,
    ): Response {
        $subjectId = $this->subject->id();

        if ($subjectId === null) {
            throw new UnauthorizedHttpException('Bearer', 'Unauthenticated.');
        }

        $scopeId = $this->scope->current();

        foreach ($permissions as $permission) {
            if (! $this->authorizer->can($subjectId, $permission, $scopeId)) {
                throw new AccessDeniedHttpException('This action is unauthorized.');
            }
        }

        return $next($request);
    }
}
