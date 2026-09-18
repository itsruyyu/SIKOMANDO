# SIKOMANDO — Prosedur Rollback Deployment

Dokumen ini mendokumentasikan langkah darurat pemulihan versi rilis (Deployment Rollback) saat terjadi insiden fatal atau kegagalan rilis pada staging / production.

---

## 1. Kondisi Pemicu Rollback (Rollback Triggers)
Rollback wajib dieksekusi jika salah satu kondisi berikut terpenuhi pasca-deployment:
1. Endpoint `/api/health` mengembalikan status non-200 atau `unhealthy`.
2. Error rate API meningkat di atas ambang batas toleransi (> 2% requests menghasilkan HTTP 500 dalam 5 menit).
3. Terjadi data corruption atau kegagalan eksekusi migrasi skema database yang memblokir transaksi pengguna.
4. Terjadi security vulnerability kritis yang teridentifikasi sesaat setelah rilis.

---

## 2. Prosedur Rollback Cepat (Fast Rollback Workflow)

### Langkah 1: Aktifkan Maintenance Mode Segera
```bash
cd /var/www/sikomando/backend
php artisan down --secret="emergency-admin-bypass-key"
```

### Langkah 2: Kembalikan Kode Aplikasi (Git Revert / Checkout)
```bash
# Kembalikan ke tag rilis sebelumnya yang stabil
git fetch --tags
git checkout tags/v1.0.0-stable -f
```

### Langkah 3: Revert Dependencies & Build
```bash
composer install --no-dev --optimize-autoloader
cd ../frontend
npm ci
npm run build
cd ../backend
```

### Langkah 4: Rollback Database Migration (Bila Diperlukan)
Jika rilis sebelumnya menambahkan migrasi baru yang bermasalah:
```bash
# Periksa batch migrasi terakhir
php artisan migrate:status

# Rollback 1 batch migrasi terakhir
php artisan migrate:rollback --step=1 --force
```
*Perhatian: Jika migrasi melibatkan penghapusan kolom atau transformasi data destruktif, pulihkan dari snapshot backup dump sebelum deployment.*

### Langkah 5: Bersihkan dan Bangun Ulang Cache Optimasi
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Langkah 6: Restart Layanan Latar Belakang
```bash
sudo systemctl restart sikomando-worker
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

### Langkah 7: Smoke Test & Cabut Maintenance Mode
```bash
curl -i https://sikomando.go.id/api/health
# Jika respons healthy (HTTP 200), aktifkan kembali aplikasi
php artisan up
```

