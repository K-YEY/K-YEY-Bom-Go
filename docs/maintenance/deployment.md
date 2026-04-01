# Maintenance & Deployment Guide

This document defines the process for deploying and maintaining K-YEY in a production environment.

## 1. Production Deployment Workflow

1. **Repo Up**: `git pull origin main`.
2. **Backend Migration**:

    ```bash
    composer install --no-dev --optimize-autoloader
    php artisan migrate --force
    php artisan optimize
    ```

3. **Frontend Build**:

    ```bash
    npm install
    npm run build
    ```

4. **Restart**: Ensure PHP-FPM or Apache/Nginx is correctly serving the `public/` directory.

## 2. Server Infrastructure (Recommended)

- **Web Server**: Nginx or Apache.
- **PHP**: PHP 8.2+ with `opcache` enabled for performance.
- **Queue Worker**: `php artisan queue:work --queue=default`.
- **Cron Jobs**:

  ```bash
  * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  ```

## 3. Environment Protection (.env)

- **APP_DEBUG**: Always `false` on production.
- **APP_ENV**: Always `production`.
- **FORCE_HTTPS**: Enable if behind a Proxy or Load Balancer.

## 4. Regular Maintenance Tasks

- **Log Cleaning**: Periodically clear `storage/logs/laravel.log` or set up `logrotate`.
- **Database Backups**: Use `spatie/laravel-backup` or a manual cron.
- **Session Cleanup**: `php artisan session:table` cleanup if using MySQL session driver.

## 5. Security Checklist

- [ ] **APP_KEY**: Ensure it's secret and generated only once.
- [ ] **Directory permissions**: Block execution of PHP in any `storage/app/public` subdirectories.
- [ ] **Sanctum Domains**: Double-check CORS and CSRF domains in `.env`.
- [ ] **SSL Certification**: Use Let's Encrypt or a commercial SSL Cert.

## 6. How to Deploy the Documentation

The documentation is a static VitePress site. To deploy:

1. **Build**: `npm run docs:build`.
2. **Upload**: Copy `docs/.vitepress/dist` to your web server (e.g. `/var/www/docs`).
3. **Serve**: Point a subdomain (like `docs.yourproject.com`) to that directory.
