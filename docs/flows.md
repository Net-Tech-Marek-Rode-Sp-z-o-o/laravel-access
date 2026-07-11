# Flows

End-to-end behaviour of the package. Each flow is covered by a test in `tests/` (unit for the
domain and the handlers, Testbench + Postgres for the rest).

## Defining the permission catalog

Permissions are **code**: the host declares a backed enum and versions it in its repository. The
package never stores them — a permission only exists in the database as a row of
`access_role_permission` attached to a role.

## Creating a role

`RoleCatalog::create(name, label, permissions)` → `CreateRole` command.

1. The name is normalised (lower-case slug) — a duplicate is rejected with
   `RoleNameAlreadyTakenException` (**422**).
2. The `Role` aggregate is created with its permission set and persisted; `RoleCreated` is
   published.
3. The new role id is returned.

`RoleCatalog::setPermissions(roleId, permissions)` replaces the whole set — the aggregate records
`RolePermissionsChanged` only when the set actually differs. `RoleCatalog::delete(roleId)` removes
the role; its permissions and assignments cascade away with it.

## Assigning a role

`RoleAssignments::assign(subjectId, role, scopeId = null)` → `AssignRole` command.

1. The role is looked up by name (unknown → `RoleNotFoundException`, **404**).
2. An existing identical assignment makes the command a no-op (idempotent).
3. Otherwise a `RoleAssignment` is granted and persisted; `RoleAssigned` is published.

`scopeId = null` grants **globally**; a value grants **only inside that scope**. The same subject
can be `manager` in store A and `customer` in store B — two scoped assignments on one subject.

## Revoking a role

`RoleAssignments::revoke(subjectId, role, scopeId = null)` → `RevokeRole` command.

The matching `(role, subject, scope)` assignment is loaded, records `RoleRevoked`, and its row is
deleted. Revoking something that is not assigned is a no-op. Revoking in one scope leaves the
subject's assignments in other scopes untouched.

## Checking a permission

`Authorizer::can(subjectId, permission, scopeId)`.

1. The `(subject, scope)` permission set is resolved with **one query**: every role the subject
   holds **in that scope or globally**, joined to that role's permissions.
2. The set is memoized for the rest of the request — further checks cost no query.
3. `can()` is a set lookup; `hasRole()`, `rolesOf()` and `permissionsOf()` read the same resolved
   set.

A grant is denied unless some role of the subject — global, or scoped to the scope being checked —
carries the permission.

## Protecting a route

`->middleware('permission:invoices.issue')`.

1. `CurrentSubject::id()` gives the subject — `null` → **401**.
2. `ScopeContext::current()` gives the scope of the request (`null` in single-tenant apps).
3. `Authorizer::can()` decides: **403** on denial, otherwise the request continues.

Several permissions in one alias (`permission:a,b`) are **all** required.
