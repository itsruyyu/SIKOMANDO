# SIKOMANDO — Production Readiness Verification

Dokumen ini mendefinisikan kriteria kesiapan produksi (Production Readiness Criteria) untuk sistem **SIKOMANDO (Sistem Informasi Komprehensif Manajemen Digitalisasi Hibah Organisasi)** sebelum dirilis ke lingkungan Staging dan Production.

---

## 1. Prerequisite Server & Runtime
- **Operating System**: Linux Enterprise (Ubuntu 22.04 LTS / 24.04 LTS atau RHEL 9+)
- **PHP**: PHP 8.2+ dengan ekstensi wajib:
  - `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `bcmath`, `curl`, `json`, `fileinfo`, `xml`, `zip`, `intl`, `redis` (opsional)
  - `memory_limit >= 512M`
  - `max_execution_time >= 120`
  - `upload_max_filesize >= 20M`
  - `post_max_size >= 25M`
- **PostgreSQL**: PostgreSQL 16+ atau 17+
  - Ekstensi: `uuid-ossp` atau `pgcrypto`
  - Encoding: `UTF8`
- **Node.js**: Node.js 20 LTS atau 22 LTS, npm 10+ (untuk build aset frontend)
- **Web Server**: Nginx 1.24+ dengan reverse proxy ke PHP-FPM / Octane
- **Storage**: SSD NVMe dengan partisi khusus untuk private document storage (`storage/app/private`)

---

## 2. Security & Environment Baseline
- [x] **APP_ENV**: `production`
- [x] **APP_DEBUG**: `false` (Wajib! Jangan pernah aktifkan debug mode di production)
- [x] **APP_KEY**: Terdefinisi dan tersimpan aman di `.env` (hasil `php artisan key:generate`)
- [x] **HTTPS/TLS**: Sertifikat SSL/TLS aktif (Grade A Qualys SSL Labs)
- [x] **OWASP Security Headers**:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`
- [x] **Correlation ID**: Header `X-Request-ID` diaktifkan pada semua respons HTTP
- [x] **Sanctum Token Lifetime**: Token kedaluwarsa sesuai kebijakan, user nonaktif otomatis ditolak oleh middleware `EnsureUserIsActive`
- [x] **Maker-Checker Governance**: Kebijakan dan penugasan mencegah pembuat menyetujui aksinya sendiri

---

## 3. Directory Permissions
```bash
sudo chown -R www-data:www-data /var/www/sikomando
sudo find /var/www/sikomando -type f -exec chmod 644 {} \;
sudo find /var/www/sikomando -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/sikomando/backend/storage
sudo chmod -R 775 /var/www/sikomando/backend/bootstrap/cache
```

---

## 4. Production Readiness Status: CONDITIONALLY READY
- **Automated Testing**: 247 passed tests, 1173 assertions, 0 failure.
- **Identified Gaps**:
  1. Queue worker saat ini menggunakan driver `database` (perlu supervisor/systemd daemon pada host production).
  2. Laravel Scheduler belum memiliki recurring scheduled task terdaftar (`schedule:list` empty) — perlu didefinisikan untuk cleanup log & reminder deadline.
  3. Dokumen privat berada pada disk lokal — untuk setup multi-server / clustering, disarankan integrasi S3/MinIO.

