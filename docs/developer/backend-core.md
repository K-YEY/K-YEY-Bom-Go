# Developer Handover: Backend & Web Core

This document is essential for any developer taking over the K-YEY project.

## Architecture Decisions

- **Framework**: Laravel 12.0+ (PHP 8.2+).
- **Architecture**: Service-oriented monolith.
- **Interactions**: REST API (Stateless) using Laravel Sanctum for API token-based authentication.
- **Frontend**: Vue 3 (Composition API) + TypeScript + Vuetify.

## Code Organization (Backend)

- **Controllers**: `app/Http/Controllers/Api/` contains all feature logic.
- **Models**: `app/Models/` uses Eloquent with specific traits for role-based filtering.
- **Permissions**: Defined in `app/Support/Permissions/` and synchronized via `SyncPermissionsSeeder`.

### Important Patterns

#### 1. Data Filtering (ScopesByUserRole)

We use a trait `app/Traits/ScopesByUserRole.php` on the `Order` model. **NEVER** query orders without considering this scope:

```php
$orders = Order::query()->forUserRole()->get();
```

This ensures admins see everything, while Clients and Shippers only see their own orders.

#### 2. Permission Checks (authorizePermission)

Most controllers include a `private authorizePermission()` method. It translates a Permission string to a `Gate` check.

```php
$this->authorizePermission($request, 'order.view');
```

This pattern ensures consistency across the API and respects the `super-admin` bypass defined in `AppServiceProvider`.

#### 3. Activity Tracking (EntityObserver)

Almost every important model (`Order`, `User`, `Expense`, etc.) uses the `EntityObserver`. This automatically logs all CRUD operations into the `activity_logs` table for audit purposes.

## Code Organization (Frontend)

- **Directory**: `resources/ts/`.
- **Navigation**: `resources/ts/navigation/vertical/`.
- **Pages**: `resources/ts/pages/` (using file-based routing via `vite-plugin-vue-router`).
- **RBAC**: Controlled by CASL (`resources/ts/plugins/casl/`).

### How to trace a request

1. **UI**: Look for a Vue component in `resources/ts/pages/` or `resources/ts/views/`.
2. **API**: Find the `$api` call endpoint.
3. **Routes**: Check `routes/api.php` for the controller mapping.
4. **Controller**: Check the method in `app/Http/Controllers/Api/`.
5. **Role Check**: Look for `authorizePermission` inside the controller method.
6. **Scope Check**: Ensure `forUserRole()` is called on the query.

## Common Pitfalls

1. **Permission Sync**: If you add a permission, you MUST add it to the relevant `Map` class (e.g. `OrdersPermissionMap`) and run `php artisan db:seed --class=SyncPermissionsSeeder`.
2. **Frontend CASL Cache**: After adding permissions or changing roles, you MUST re-login on the frontend to update the local rules.
3. **Final Statuses**: Orders marked as `DELIVERED` or `SHIPPED` have restricted update logic. Check `authorizeFinalStatusUpdate()` in `OrderController`.
