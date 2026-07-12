# Flows

End-to-end behaviour of the package. Each flow is covered by a test in `tests/` (unit for the
domain and the handlers, Testbench + Postgres for the rest).

## Defining the permission catalog

Permissions are **code**: the host declares a backed enum and versions it in its repository. The
package never stores them — a permission only exists in the database as a row of
`access_role_permission` attached to a role.

The host binds that enum to the `PermissionCatalog` port, and from then on `CreateRole` and
`SetRolePermissions` refuse anything the application does not declare
(`UnknownPermissionException`, **422**). The default adapter declares nothing, and an empty catalog
means *the host has not told us*, not *nothing is allowed* — there is nothing to check against, so
every string passes. `ListPermissions` reads the same port, which is what an admin UI lists.

## Creating a role

`RoleCatalog::create(name, label, permissions)` → `CreateRole` command.

1. The name is normalised (lower-case slug) — a duplicate is rejected with
   `RoleNameAlreadyTakenException` (**422**).
2. Every permission must be declared by the `PermissionCatalog` — see above.
3. The `Role` aggregate is created with its permission set and persisted; `RoleCreated` is
   published.
4. The new role id is returned.

`RoleCatalog::setPermissions(roleId, permissions)` replaces the whole set, under the same catalog
check — the aggregate records `RolePermissionsChanged` only when the set actually differs.
`RoleCatalog::delete(roleId)` removes the role; its permissions and assignments cascade away with
it.

## Assigning a role

`RoleAssignments::assign(subjectId, role, scopeId = null)` → `AssignRole` command.

The commands are keyed by role **id**. The port takes a role **name** — a string or the host's own
enum, which is what a host has at hand — so it first resolves the name on the read side
(`GetRoleByName`, unknown → `RoleNotFoundException`, **404**) and dispatches the id.

1. The role is loaded by id (unknown → `RoleNotFoundException`, **404**).
2. An existing identical assignment makes the command a no-op (idempotent).
3. Otherwise a `RoleAssignment` is granted and persisted; `RoleAssigned` is published.

`scopeId = null` grants **globally**; a value grants **only inside that scope**. The same subject
can be `manager` in store A and `customer` in store B — two scoped assignments on one subject.

## Revoking a role

`RoleAssignments::revoke(subjectId, role, scopeId = null)` → `RevokeRole` command.

The name is resolved to an id the same way, then the matching `(role, subject, scope)` assignment is
loaded, records `RoleRevoked`, and its row is deleted. Revoking something that is not assigned is a
no-op. Revoking in one scope leaves the subject's assignments in other scopes untouched.

## Checking a permission

`Authorizer::can(subjectId, permission, scopeId)`.

1. The `(subject, scope)` permission set is resolved with **one query**: every role the subject
   holds **in that scope or globally**, joined to that role's permissions.
2. The set is memoized for the rest of the request — further checks cost no query.
3. `can()` is a set lookup; `hasRole()`, `rolesOf()` and `permissionsOf()` read the same resolved
   set.

A grant is denied unless some role of the subject — global, or scoped to the scope being checked —
carries the permission.

## Reading the catalogue

`ListRoles` / `GetRole` / `GetRoleByName` queries → `RoleReadModel` → `list<RoleView>` / `RoleView`.

The read side never touches the aggregates: one join over `access_roles × access_role_permission`
returns every role with its permissions, so listing the catalogue costs a single query no matter how
many roles there are. `GetRole` / `GetRoleByName` on an unknown role raise `RoleNotFoundException`
(**404**).

`ListPermissions` is the odd one out — it reads no table at all, only the `PermissionCatalog` the
host declared in code.

## Managing roles over HTTP

Every admin route runs behind two middlewares. `RequirePermission:{config('access.admin_permission')}`
answers *may this subject administer at all* — the admin is just a subject holding that permission,
resolved like any other check (no subject → **401**, no permission → **403**).

`RequireScopeOwnership` then answers *may it administer **here***, and only for writes:

1. The subject holding the permission **globally** is a platform admin — it passes, whatever the
   request names.
2. Anybody else holds it only inside a scope, so the `scope_id` of the payload must equal
   `ScopeContext::current()`; otherwise **403**. Omitting `scope_id` means the global scope, so a
   scoped admin cannot grant globally either.
3. The role catalogue carries no `scope_id` at all — roles are global — so a scoped admin is
   refused there outright and can only read the list.

Without this second check the middleware pair would be trivially bypassable: a store-A admin passes
the permission check on a store-A request and could then name any other scope, or none, in the body
— granting itself platform-wide roles. The permission check alone constrains *where you ask from*,
not *what you ask for*.

1. `POST /access/roles` → `CreateRole` → **201** `{data: {id}}`; a duplicate name → **422**.
2. `GET /access/roles` → `ListRoles` → **200** `{data: [{id, name, label, permissions}]}`.
3. `GET /access/permissions` → `ListPermissions` → **200** `{data: ["invoices.issue", …]}`.
4. `PUT /access/roles/{role_id}/permissions` → `SetRolePermissions` → **204**; the set is replaced
   wholesale and the flushed memo makes the very next check see the new permissions. An undeclared
   permission → **422**.
5. `DELETE /access/roles/{role_id}` → `DeleteRole` → **204**; permissions and assignments cascade.

## Assigning a role over HTTP

`POST /access/subjects/{subject_id}/roles` with `{role_id, scope_id?}`.

The API and the commands are both keyed by role id, so the controller dispatches `AssignRole`
straight from the request data — no lookup of its own, and the handler's own `getById` is what
answers **404** for an unknown role. `DELETE /access/subjects/{subject_id}/roles/{role_id}?scope_id=`
mirrors it onto `RevokeRole`. Both answer **204**, and both are idempotent, exactly like the ports
they wrap.

Omitting `scope_id` means the global scope: assigning without one grants everywhere, revoking
without one removes only the global assignment and leaves scoped ones in place.

## Protecting a route

`->middleware('permission:invoices.issue')`.

1. `CurrentSubject::id()` gives the subject — `null` → **401**.
2. `ScopeContext::current()` gives the scope of the request (`null` in single-tenant apps).
3. `Authorizer::can()` decides: **403** on denial, otherwise the request continues.

Several permissions in one alias (`permission:a,b`) are **all** required.
