# Panduan Setup & Deployment SIKOMANDO

Panduan teknis penyiapan lingkungan lokal, staging, dan production untuk aplikasi backend SIKOMANDO (Laravel 12 + PostgreSQL).

---

## 1. Persyaratan Sistem

- PHP >= 8.2 (Ekstensi: `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `gd`)
- PostgreSQL >= 14
- Composer >= 2.6
- Web Server: Nginx atau Apache (disarankan Nginx dengan PHP-FPM)
- Node.js & NPM (untuk aset frontend publik jika digabungkan)

---

## 2. Instalasi Lingkungan Baru

1. **Clone repository dan masuk ke folder backend**:
   ```bash
   cd backend
   ```

2. **Salin environment file**:
   ```bash
   cp .env.example .env
   ```

3. **Install dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Generate App Key**:
   ```bash
   php artisan key:generate
   ```

5. **Konfigurasi Database PostgreSQL di `.env`**:
   ```ini
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=sikomando
   DB_USERNAME=sikomando_user
   DB_PASSWORD=SecurePasswordHere
   ```

6. **Jalankan Migrasi & Seeder Master Data**:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=RolePermissionSeeder --force
   php artisan db:seed --class=AdminUserSeeder --force
   php artisan db:seed --class=MasterDataSeeder --force
   php artisan db:seed --class=AnnouncementSeeder --force
   ```

7. **Link Storage**:
   ```bash
   php artisan storage:link
   ```

---

## 3. Konfigurasi Queue Worker & Scheduler

### Queue Worker (Systemd Service)
Buat file service `/etc/systemd/system/sikomando-worker.service`:
```ini
[Unit]
Description=SIKOMANDO Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/sikomando/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```
Aktifkan service:
```bash
sudo systemctl enable --now sikomando-worker
```

### Cron Scheduler
Tambahkan ke crontab user web server (`crontab -e -u www-data`):
```cron
* * * * * cd /var/www/sikomando/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Pencadangan (Backup) & Pemulihan (Restore) Database

### Prosedur Backup
```bash
pg_dump -h 127.0.0.1 -U postgres -d sikomando -F c -b -v -f /backup/sikomando_$(date +\%Y\%m\%d_\%H\%M\%S).dump
```

### Prosedur Restore
```bash
pg_restore -h 127.0.0.1 -U postgres -d sikomando -v /backup/sikomando_target.dump
```

