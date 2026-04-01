# Developer Handover: Setup & Infrastructure

Follow these steps to set up the K-YEY project locally.

## Project Prerequisites

- **PHP 8.2+** with Composer 2.x.
- **Node.js 20+** (LTS recommended) and **PNPM** or **NPM**.
- **Database**: MySQL 8.0, PostgreSQL, or Sqlite.
- **Flutter SDK 3.x** for the mobile app.

## Web Setup

1. **Repo Clone**: `git clone <repository_url>`.
2. **Backend Init**:

    ```bash
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan migrate:fresh --seed
    ```

3. **Frontend Init**:

    ```bash
    npm install
    npm run dev
    ```

4. **Admin Login**: Use `admin` with password `12345678`.

## Mobile Setup

1. **Navigate**: `cd mobile_app_directory`.
2. **Deps**: `flutter pub get`.
3. **Env**: Configure `lib/core/constants/api_config.dart`.
4. **Run**: `flutter run`.

## Environment Variables (.env)

- `APP_URL`: The URL of your Laravel API.
- `SANCTUM_STATEFUL_DOMAINS`: Used by Sanctum for local/prod domains (e.g. `localhost:3000`).
- `SESSION_DOMAIN`: The domain for session cookies (e.g. `.localhost`).
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

## Local Build Commands

- `npm run build`: Minify and build the Vue 3 dashboard for production.
- `npm run lint`: Check for potential code quality issues.
- `php artisan config:cache`: Apply configuration changes to the server.

## Directory permissions

Ensure `storage/` and `bootstrap/cache/` are writable by the web server (e.g. `www-data`).
