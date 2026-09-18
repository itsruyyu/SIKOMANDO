# SIKOMANDO — Prosedur Backup & Restore Database dan Dokumen

Dokumen ini adalah standar operasional prosedur (SOP) pencadangan (backup) dan pemulihan (restore) sistem SIKOMANDO.

---

## 1. Strategi Backup
- **Frekuensi**:
  - Database PostgreSQL: Setiap 6 jam (incremental WAL archiving) dan harian (full logical dump pukul 02:00 WIB).
  - Private Documents (`storage/app/private`): Sinkronisasi harian dengan `rsync` atau snapshot volume storage.
- **Lokasi Penyimpanan**:
  - Lokal: `/var/backups/sikomando/`
  - Offsite: Secondary S3-compatible Object Storage dengan retensi 30 hari dan proteksi immutability (WORM/Object Lock).
- **Format Dump**: Format Custom Compressed (`pg_dump -F c`) untuk kompresi maksimal dan fleksibilitas restore parsial.

---

## 2. Perintah Backup Otomatis

### Script: `/usr/local/bin/backup-sikomando.sh`
```bash
#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR="/var/backups/sikomando"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME="sikomando"
DB_USER="postgres"
DUMP_FILE="${BACKUP_DIR}/db_${DB_NAME}_${TIMESTAMP}.dump"
DOCS_ARCHIVE="${BACKUP_DIR}/docs_${TIMESTAMP}.tar.gz"

mkdir -p "${BACKUP_DIR}"

echo "[$(date)] Starting SIKOMANDO database backup..."
pg_dump -h 127.0.0.1 -p 5432 -U "${DB_USER}" -F c -b -f "${DUMP_FILE}" "${DB_NAME}"
echo "[$(date)] Database backup completed: ${DUMP_FILE} ($(du -h "${DUMP_FILE}" | cut -f1))"

echo "[$(date)] Starting storage private documents backup..."
tar -czf "${DOCS_ARCHIVE}" -C /var/www/sikomando/backend/storage/app private
echo "[$(date)] Documents backup completed: ${DOCS_ARCHIVE} ($(du -h "${DOCS_ARCHIVE}" | cut -f1))"

# Retention: Hapus backup lokal lebih dari 14 hari
find "${BACKUP_DIR}" -type f -name "*.dump" -mtime +14 -delete
find "${BACKUP_DIR}" -type f -name "*.tar.gz" -mtime +14 -delete
```

---

## 3. Prosedur Restore Database

### Langkah 1: Persiapan & Maintenance Mode
```bash
cd /var/www/sikomando/backend
php artisan down --message="Pemeliharaan sistem terjadwal sedang berlangsung. Mohon tunggu."
```

### Langkah 2: Verifikasi File Dump
```bash
pg_restore -l /var/backups/sikomando/db_sikomando_YYYYMMDD_HHMMSS.dump | head -n 30
```

### Langkah 3: Eksekusi Restore
```bash
# Drop & recreate target database
dropdb -h 127.0.0.1 -U postgres sikomando
createdb -h 127.0.0.1 -U postgres -O sikomando_user sikomando

# Restore schema & data
pg_restore -h 127.0.0.1 -p 5432 -U postgres -d sikomando --clean --if-exists --no-owner /var/backups/sikomando/db_sikomando_YYYYMMDD_HHMMSS.dump
```

### Langkah 4: Restore Dokumen Privat
```bash
tar -xzf /var/backups/sikomando/docs_YYYYMMDD_HHMMSS.tar.gz -C /var/www/sikomando/backend/storage/app/
chown -R www-data:www-data /var/www/sikomando/backend/storage/app/private
```

### Langkah 5: Verifikasi & Exit Maintenance Mode
```bash
php artisan migrate:status
php artisan health:check # atau curl /api/health
php artisan up
```

