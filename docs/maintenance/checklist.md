# Pre-Handover Checklist for Next Developer

## 1. Local Onboarding

- [ ] Install **PHP 8.2+**, **Composer**, **Node.js 20+**, and **Flutter 3.x**.
- [ ] Configure `.env` with a local database (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- [ ] Run `composer install` and `npm install`.
- [ ] Run `php artisan key:generate`.
- [ ] Run `php artisan migrate --seed` to populate required tables (Governorates, Refused Reasons, Admin, etc.).
- [ ] Verify you can log in as `admin` (pass: `12345678`).
- [ ] Run `flutter pub get` in the mobile project.

## 2. Infrastructure Setup

- [ ] Add your public key to the production server.
- [ ] Verify **Sanctum** `SANCTUM_STATEFUL_DOMAINS` and `SESSION_DOMAIN`.
- [ ] Ensure **Redis** is available for queues (if used).
- [ ] Verify file upload directory permissions (`storage/app/public`).

## 3. Critical Modules to Read FIRST

- [ ] `app/Traits/ScopesByUserRole.php`: How multi-role data filtering works.
- [ ] `app/Http/Controllers/Api/Orders/OrderController.php`: The main order management logic.
- [ ] `app/Support/Permissions/`: How ACL and permissions are mapped.
- [ ] `lib/core/network/api_client.dart` (Flutter): How API calls are signed.
- [ ] `lib/business_logic/cubits/` (Flutter): Representative app logic.

## 4. Technical Debt & Risks

- [ ] **Manual Permission Sync**: Remember to run the seeder after any ACL change.
- [ ] **Hardcoded Statuses**: Several statuses are used as strings (e.g., `OUT_FOR_DELIVERY`). These should ideally be moved to an Enum.
- [ ] **Test Coverage**: Initial version lacks full automated test coverage. High manual unit testing is required.
- [ ] **Financial Logic**: Be extremely careful when editing `ShipperCollection` and `ClientSettlement` logic as they update `Order` states in transactions.

## 5. Security Best Practices

- [ ] **Sanitization**: All input should be validated via `FormRequest` or `request->validate()`.
- [ ] **Mass Assignment**: Check `$fillable` in models.
- [ ] **Audit Logs**: Ensure all new modules implement the `EntityObserver`.

## 6. Upgrade Priorities

- [ ] Refactor order statuses to PHP 8.1+ **Enums**.
- [ ] Add **Units Tests** for the settlement algorithm.
- [ ] Implement **Sentry** or BugSnag for error monitoring.
- [ ] Optimize the `forUserRole` scope for large datasets (add necessary composite indexes).
