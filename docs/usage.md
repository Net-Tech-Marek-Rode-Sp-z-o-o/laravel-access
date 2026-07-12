# Usage

## Install

```bash
composer require net-code/laravel-access
php artisan vendor:publish --tag=access-config   # optional
php artisan migrate
```

The service provider is auto-discovered. It ships three migrations (`access_roles`,
`access_role_permission`, `access_role_user`) and registers the `permission:` route middleware
alias.

## Model

- **Permission** — an opaque string id, **defined in your code** (a backed enum). No permissions
  table.
- **Role** — a named set of permissions, **data** (`access_roles`), manageable at runtime.
- **Assignment** — `(role, subject, scope)`. `scope_id = null` means a **global** grant.

Define your catalog in the host:

```php
enum Permission: string
{
    case InvoicesIssue = 'invoices.issue';
    case UsersInvite   = 'users.invite';
}
```

Every public API accepts `string|BackedEnum` and normalises to a string, so the host stays typed
while the package stays generic.

Subject and scope ids are **UUIDs** (stored as native Postgres `uuid`), passed across the ports as
plain strings — the package never learns what a subject *is*, only that it is identified by a UUID.

## Config (`config/access.php`)

| Key | Default | Purpose |
|---|---|---|
| `middleware_alias` | `permission` | alias for the `RequirePermission` middleware |
| `gate` | `true` | register `Gate::before` so `$user->can()` / Policies see the permissions |
| `table_prefix` | `access_` | prefix of the three tables (applies to migrations and queries) |

## Ports (hexagonal)

**Inbound** — resolve and use:

| Port | Methods |
|---|---|
| `Authorizer` | `can($subjectId, $permission, $scopeId = null)`, `hasRole(...)`, `rolesOf($subjectId, $scopeId = null)`, `permissionsOf(...)` |
| `RoleAssignments` | `assign($subjectId, $role, $scopeId = null)`, `revoke(...)` |
| `RoleCatalog` | `create($name, $label, $permissions = [])` → role id, `setPermissions($roleId, $permissions)`, `delete($roleId)` |

```php
public function __construct(
    private Authorizer $authorizer,
) {}

$this->authorizer->can($userId, Permission::InvoicesIssue, $tenantId);
```

`RoleCatalog` / `RoleAssignments` dispatch the package commands (`CreateRole`,
`SetRolePermissions`, `DeleteRole`, `AssignRole`, `RevokeRole`) on `net-code/laravel-bus`, so every
mutation runs inside the bus transaction. Dispatch the commands directly if you prefer.

**Outbound** — bind a host adapter to override the default:

| Port | Purpose | Default |
|---|---|---|
| `ScopeContext` | the current scope (tenant) of the request | `NullScopeContext` → `null` (single-tenant) |
| `CurrentSubject` | the current subject id, used by the middleware | `NullCurrentSubject` → `null` |
| `Clock` | time source (`net-code/laravel-kit`) | `SystemClock` |

**Internal** — swappable adapters (defaults wired): `RoleRepository` /
`RoleAssignmentRepository` → Eloquent, `Authorizer` → `DatabaseAuthorizer` (request-scoped).

## Wiring the identity bridge

The core never references `net-code/laravel-identity`. The host binds the bridge:

```php
final readonly class IdentityCurrentSubject implements CurrentSubject
{
    public function __construct(
        private CurrentUser $users,          // NetCode\Identity\Application\Ports\CurrentUser
    ) {}

    public function id(): string|null
    {
        return $this->users->userOrNull()?->id();
    }
}

$this->app->bind(CurrentSubject::class, IdentityCurrentSubject::class);
$this->app->bind(ScopeContext::class, TenantScopeContext::class);   // subdomain / header / path / token
```

Identity's `/me` `GrantedAuthorization` seam is composed the same way — the host implements it on
top of `Authorizer::rolesOf()` / `permissionsOf()` for the current `ScopeContext`, so identity stays
scope-agnostic and access stays subject-agnostic.

## Route protection

```php
Route::post('/invoices', IssueInvoiceController::class)->middleware('permission:invoices.issue');
Route::delete('/users/{id}', RemoveUserController::class)->middleware('permission:users.invite,users.remove'); // all required
```

No subject → **401**. Subject without the permission (in the current scope) → **403**.

## Laravel Gate

With `access.gate = true` (the default) the provider registers a `Gate::before` hook, so the
framework's own authorization APIs see the permissions:

```php
$user->can('invoices.issue');                       // true when a role of the user grants it
$this->authorize('invoices.issue');                 // in a controller
Route::post('/invoices', …)->can('invoices.issue'); // Laravel's own can: middleware
@can('invoices.issue') … @endcan                    // Blade
```

The subject is the authenticated user's `getAuthIdentifier()`; the scope comes from `ScopeContext`.
The hook returns `true` on a grant and **abstains** (`null`) otherwise — it never returns `false` —
so your Policies and `Gate::define()` abilities keep deciding everything the package does not grant.
A non-UUID auth identifier makes it abstain too, so a host with integer user ids is unaffected.

Set `access.gate = false` to keep the Gate untouched and rely on the `permission:` middleware and
the `Authorizer` port alone.

## Scopes (multi-tenancy)

A scope is an **opaque UUID** — the host maps its tenant id onto it. `null` = global.

- a role assigned at `scope = null` grants in **every** scope (platform admin);
- a role assigned at `scope = <tenant>` grants **only** when checking that scope;
- single-tenant apps never set a scope, so every grant is global.

Data isolation (row/DB filtering per tenant) is the host's job, not this package's.

## Performance

`DatabaseAuthorizer` is bound **request-scoped**. The first check for a `(subject, scope)` pair
resolves roles + permissions with **one query** (a join over `access_role_user × access_roles ×
access_role_permission`, matching the scope *and* the global scope); every further `can()` /
`hasRole()` in the request is an in-memory lookup. `RoleAssigned` / `RoleRevoked` /
`RolePermissionsChanged` flush the memo, so a check after a mutation in the same request is correct.

A distributed (Redis) cache slots in behind the same port without an API change — deliberately not
in v1 (invalidation is the hard part).

## Events

`RoleCreated`, `RolePermissionsChanged`, `RoleAssigned`, `RoleRevoked` are published through the
domain event publisher — subscribe for audit trails or cache invalidation.

## Errors

`RoleNotFoundException` → 404, `RoleNameAlreadyTakenException` → 422, invalid value objects
(`InvalidArgumentException`) → 422.

## Testing the package

```bash
docker compose run --rm test        # postgres + pint + phpstan + phpunit
```
