# SIKOMANDO — Staging Deployment Verification Checklist

Dokumen ini memandu proses deployment dan smoke testing di lingkungan Staging sebelum rilis ke Production.

---

## 1. Tahap Pra-Deployment
1. **Repository Pull**: `git checkout main && git pull origin main`
2. **Environment File**:
   - Salin `.env.example` ke `.env`
   - Sesuaikan kredensial PostgreSQL staging
   - Set `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://staging.sikomando.go.id`
3. **Backend Dependencies**: `composer install --no-dev --optimize-autoloader`
4. **Frontend Dependencies & Build**:
   ```bash
   cd frontend
   npm ci
   npm run lint
   npm run build
   ```

---

## 2. Tahap Migrasi & Database
1. **Dry-run Status**: `php artisan migrate:status`
2. **Run Migrations**: `php artisan migrate --force`
3. **Seed Master Data & Roles**:
   ```bash
   php artisan db:seed --class=RolePermissionSeeder --force
   php artisan db:seed --class=MasterDataSeeder --force
   php artisan db:seed --class=AnnouncementSeeder --force
   ```
4. **Link Storage**: `php artisan storage:link`

---

## 3. Tahap Optimasi Framework
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 4. Smoke Test & Health Check
1. **Health Check Endpoint**:
   ```bash
   curl -i https://staging.sikomando.go.id/api/health
   # Expected: HTTP 200, status: healthy, database: ok, cache: ok, storage: ok
   ```
2. **Public Portal**:
   ```bash
   curl -i https://staging.sikomando.go.id/api/v1/public/grant-programs
   # Expected: HTTP 200, list of published active programs
   ```
3. **Security Headers Verification**:
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: SAMEORIGIN`
   - `X-Request-ID: <uuid>`

---

## 5. Layanan Latar Belakang (Daemons)
1. **Queue Worker Systemd**:
   ```bash
   sudo systemctl restart sikomando-worker
   sudo systemctl status sikomando-worker
   ```
2. **Cron Scheduler**:
   ```bash
   crontab -l | grep "schedule:run"
   ```

