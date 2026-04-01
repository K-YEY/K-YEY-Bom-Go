# Troubleshooting & Maintenance

This guide helps you resolve common technical issues on the K-YEY platform.

## 1. Backend & API Issues

### Common Errors

#### "403 Forbidden" (Missing permission)

- **Symptom**: User gets a 403 error on a specific URL.
- **Fix**: Check if the user has the permission assigned in the database. Ensure you've run `php artisan db:seed --class=SyncPermissionsSeeder` after adding new permissions to the code.
- **Trace**: Check `app/Http/Controllers/Api/` for the `authorizePermission` call and its specific string.

#### "401 Unauthorized"

- **Symptom**: User is logged out unexpectedly.
- **Fix**: Check `SANCTUM_STATEFUL_DOMAINS` and `SESSION_DOMAIN` in `.env`. Ensure the frontend domain is listed.
- **Session**: Verify `config/session.php` driver. If using `database`, check the `sessions` table.

### Database Inconsistencies

#### Orders not visible

- **Symptom**: Admin can see orders but Client cannot.
- **Fix**: Check if the `client_user_id` on the `Order` model matches the Client's `id`. Ensure the `ScopesByUserRole` trait is applied correctly.

## 2. Frontend (Vue 3) Issues

### UI Components not showing

- **Symptom**: Buttons or menu items are missing.
- **Fix**: Re-login. The local `userAbilityRules` in the browser's LocalStorage might be stale.
- **Check**: Inspect `VerticalNavLink.vue` to see which permission it's checking for.

#### State not updating

- **Fix**: Ensure you are using `reactive()` or `ref()` for all data that needs to be reactive.
- **Trace**: Check the Vue DevTools for the component's state.

## 3. Mobile (Flutter + Cubit) Issues

### App Build Failures

- **Problem**: Incompatible generated files.
- **Fix**: Run `flutter clean` followed by `flutter pub get`.
- **Generate**: Run `flutter pub run build_runner build --delete-conflicting-outputs`.

#### API call fails (DioError)

- **Trace**: Use an interceptor to log requests. Check if the token is passed in `Authorization: Bearer <token>`.
- **CORS**: Ensure the Backend server allows the mobile app's User-Agent or Origin.

#### Cubit State Stuck

- **Symptom**: Loading spinner never disappears.
- **Fix**: Check the `Cubit` logic to ensure `emit()` is called in the `catch` block too.
- **Log**: Check the `BlocObserver` output in the console.

## 4. Production Maintenance

### Clearing Caches

To resolve configuration or route issues on production:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```

### Monitoring Logs

Always monitor storage logs for production errors:

```bash
tail -f storage/logs/laravel.log
```

Check `activity_logs` table for database-level changes performed by users.
