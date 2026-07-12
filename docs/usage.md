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
| `routes` | `true` | register the admin routes |
| `route_prefix` | `access` | prefix the admin routes are mounted under |
| `admin_permission` | `access.roles.manage` | permission required by every admin route (`null` → the host guards them) |
| `table_prefix` | `access_` | prefix of the three tables (applies to migrations and queries) |

## Ports (hexagonal)

**Inbound** — resolve and use:

| Port | Methods |
|---|---|
| `Authorizer` | `can($subjectId, $permission, $scopeId = null)`, `hasRole(...)`, `rolesOf($subjectId, $scopeId = null)`, `permissionsOf(...)` |
| `RoleAssignments` | `assign($subjectId, $role, $scopeId = null)`, `revoke(...)` |
| `RoleCatalog` | `create($name, $label, $permissions = [])` → role id, `setPermissions($roleId, $permissions)`, `delete($roleId)` |
| `RoleReadModel` | `all()` → `list<RoleView>`, `get($roleId)`, `getByName($name)` (a string or your role enum) → `RoleView` — the read side, behind the `ListRoles` / `GetRole` / `GetRoleByName` queries |

```php
public function __construct(
    private Authorizer $authorizer,
) {}

$this->authorizer->can($userId, Permission::InvoicesIssue, $tenantId);
```

`RoleCatalog` / `RoleAssignments` dispatch the package commands (`CreateRole`,
`SetRolePermissions`, `DeleteRole`, `AssignRole`, `RevokeRole`) on `net-code/laravel-bus`, so every
mutation runs inside the bus transaction. Dispatch the commands directly if you prefer.

Every command is keyed by the role **id** — `AssignRole(roleId:, subjectId:, scopeId:)`. The ports
are the convenient way in and take a role **name** (a string or your own enum), because that is what
a host has at hand; `BusRoleAssignments` resolves the name through `GetRoleByName` and dispatches
the id, so an unknown role fails with `RoleNotFoundException` either way.

**Outbound** — bind a host adapter to override the default:

| Port | Purpose | Default |
|---|---|---|
| `ScopeContext` | the current scope (tenant) of the request | `NullScopeContext` → `null` (single-tenant) |
| `CurrentSubject` | the current subject id, used by the middleware | `NullCurrentSubject` → `null` |
| `PermissionCatalog` | the permissions the app defines — your enum | `NullPermissionCatalog` → nothing declared, nothing validated |
| `Clock` | time source (`net-code/laravel-kit`) | `SystemClock` |

### Declaring your permissions

Bind `PermissionCatalog` over your enum and the package starts refusing a role permission the
application does not know — a typo in the admin UI becomes a **422** instead of a permission nobody
will ever hold:

```php
final readonly class AppPermissions implements PermissionCatalog
{
    /** @return iterable<Permission> */
    public function all(): iterable
    {
        return Permission::cases();
    }
}

$this->app->bind(PermissionCatalog::class, AppPermissions::class);
```

Leave it unbound (the default) and the catalog is empty, which means *undeclared*, not *nothing
allowed*: the package has nothing to check against, so any string is accepted. The same catalog
backs `GET /access/permissions`, which is what an admin UI lists in its permission picker.

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

## Admin API

The package mounts a thin role-management API under `config('access.route_prefix')` (default
`access`), on the `api` middleware group:

| Method | Path | Body / query | Success |
|---|---|---|---|
| `GET` | `/access/permissions` | — | **200** `{data: ["invoices.issue", …]}` (what `PermissionCatalog` declares) |
| `GET` | `/access/roles` | — | **200** `{data: [{id, name, label, permissions: []}]}` |
| `POST` | `/access/roles` | `{name, label}` | **201** `{data: {id}}` |
| `DELETE` | `/access/roles/{role_id}` | — | **204** |
| `PUT` | `/access/roles/{role_id}/permissions` | `{permissions: []}` (replaces the whole set) | **204** |
| `POST` | `/access/subjects/{subject_id}/roles` | `{role_id, scope_id?}` | **204** |
| `DELETE` | `/access/subjects/{subject_id}/roles/{role_id}` | `?scope_id=` | **204** |

Input is validated (`spatie/laravel-data`, snake_case keys); every non-empty success body is wrapped
in `{"data": …}` (`JsonResource`) with snake_case fields. Errors reuse the package's renderable
exceptions: unknown role → **404**, duplicate role name / undeclared permission / invalid value →
**422**. Path segments are constrained to UUIDs, so a malformed `{role_id}` / `{subject_id}` matches
no route → **404**.

Omitting `scope_id` means the **global** scope — an assignment without a scope grants everywhere,
and a revoke without a scope only removes the global assignment (a scoped one survives).

**The admin routes are guarded by `config('access.admin_permission')`** (default
`access.roles.manage`), enforced by `RequirePermission` like any other route: no subject → 401, no
permission → 403. The host must **declare that permission in its own catalog and attach it to an
admin role** — the package ships no seed, so until you grant it to somebody, nobody can call these
endpoints:

```php
enum Permission: string
{
    case RolesManage = 'access.roles.manage';   // guards the admin API
    case InvoicesIssue = 'invoices.issue';
}

$roles->create(name: 'global-admin', label: 'Global admin', permissions: [Permission::RolesManage]);
$assignments->assign(subjectId: $founderId, role: 'global-admin');    // global: no scope
```

### Global admins vs scoped admins

**How you grant the admin permission decides how much it buys**, because `RequireScopeOwnership`
runs behind `RequirePermission` on every write:

| The subject holds `admin_permission`… | Reads | Assign / revoke | Role catalogue (create, delete, permissions) |
|---|---|---|---|
| **globally** (`scope_id = null`) | ✅ | ✅ any subject, any scope | ✅ |
| **inside a scope** | ✅ | ✅ only with `scope_id` = the request's current scope | ❌ **403** |

A scoped admin is a *tenant* admin: it may hand roles out **inside its own tenant** and nothing
else. It cannot grant globally (`scope_id` omitted), cannot grant into another scope, and cannot
touch the role catalogue at all — roles are global objects with no `scope_id`, so editing or
deleting one would reach outside the tenant. Only a **globally** granted admin permission is a
platform admin.

The catalogue is still shared: a scoped admin can *read* the role list (it needs the ids to assign
them), so do not put tenant-confidential information in a role label.

Within a tenant, the permission is unrestricted: a tenant admin may grant **any** role of the
catalogue — including one carrying permissions it does not itself hold — to anybody, scoped to its
tenant. If you need to stop that, wrap `RoleAssignments` in your own policy.

Set `admin_permission` to `null` to mount the routes unguarded (both middlewares are skipped — you
protect them yourself).

To wire the routes yourself, publish `access-routes` (it lands in `routes/access.php`) **and set
`routes` to `false`**. Those two go together: the package registers its own group in the provider,
so a published copy that the host also loads means the same URIs are declared twice, and the copy
registered last — yours, with whatever middleware you gave it — is the one that answers. Leaving
`routes` on `true` therefore silently shadows the guarded group with the published one.

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

`RoleNotFoundException` → 404, `RoleNameAlreadyTakenException` → 422, `UnknownPermissionException`
(a permission the `PermissionCatalog` does not declare) → 422, invalid value objects
(`InvalidArgumentException`) → 422.

## Testing the package

```bash
docker compose run --rm test        # postgres + pint + phpstan + phpunit
```
