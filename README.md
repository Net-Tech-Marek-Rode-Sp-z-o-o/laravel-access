# net-code/laravel-access

Reusable **RBAC authorization** for Laravel — roles as data, permissions as code, optional
scopes — built with hexagonal ports & adapters. Authentication (who you are) is a separate
concern → `net-code/laravel-identity`.

```bash
composer require net-code/laravel-access
php artisan migrate
```

```php
$authorizer->can($userId, Permission::InvoicesIssue, $tenantId);   // host enum, typed
Route::post('/invoices', IssueInvoiceController::class)->middleware('permission:invoices.issue');
```

- **`docs/usage.md`** — install, config, ports, wiring.
- **`docs/flows.md`** — the end-to-end flows.

## v1 scope

Roles + permissions · role↔permission mapping · subject↔role assignment (global or scoped) ·
`Authorizer` (one query per request, memoized) · `permission:` route middleware. Role hierarchy,
admin HTTP routes, a distributed cache and `Gate` registration are seamed next steps.

The core is **subject-agnostic** (`subjectId: string`): it never depends on the identity
package — the host wires the bridge.

## License

MIT
