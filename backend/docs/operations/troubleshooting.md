# SIKOMANDO — Panduan Pemecahan Masalah (Troubleshooting Guide)

Dokumen ini berisi panduan penanganan kendala umum yang mungkin terjadi di lingkungan Staging dan Production SIKOMANDO.

---

## 1. Masalah Koneksi Database (PostgreSQL)

### Gejala:
- Endpoint `/api/health` mengembalikan HTTP 503 `status: unhealthy`.
- Error log: `SQLSTATE[08006] [7] could not connect to server: Connection refused`.

### Langkah Penanganan:
1. Periksa status layanan PostgreSQL:
   ```bash
   sudo systemctl status postgresql
   ```
2. Pastikan port 5432 aktif dan menerima koneksi:
   ```bash
   sudo ss -tlpn | grep 5432
   ```
3. Periksa kuota koneksi maksimum (`max_connections`) pada `postgresql.conf`:
   ```sql
   SELECT count(*) FROM pg_stat_activity;
   SHOW max_connections;
   ```
4. Jika terjadi connection exhaustion, restart layanan atau gunakan connection pooler (PgBouncer).

---

## 2. Token Sanctum Ditolak (HTTP 403 Forbidden)

### Gejala:
Pengguna telah memiliki token aktif tetapi menerima pesan:
`"Akun Anda sedang dinonaktifkan. Silakan hubungi administrator."`

### Penyebab:
Kolom `is_active` pengguna pada database bernilai `false`.

### Langkah Penanganan:
1. Hubungi Super Admin untuk memeriksa status akun pengguna di menu Manajemen User atau database:
   ```sql
   SELECT id, name, email, is_active FROM users WHERE email = 'user@example.com';
   ```
2. Aktifkan kembali via API `POST /api/v1/users/{id}/toggle-active` oleh Super Admin.

---

## 3. Storage Link Terputus atau Dokumen Tidak Ditemukan (404)

### Gejala:
Pengguna gagal mengunduh dokumen proposal atau berkas LPJ.

### Langkah Penanganan:
1. Pastikan storage link telah dibuat:
   ```bash
   cd /var/www/sikomando/backend
   php artisan storage:link
   ```
2. Periksa izin direktori dokumen privat:
   ```bash
   ls -ld storage/app/private
   sudo chown -R www-data:www-data storage/app/private
   sudo chmod -R 775 storage/app/private
   ```

---

## 4. Queue Worker Macet atau Berhenti

### Gejala:
Notifikasi email atau pemrosesan latar belakang tidak terkirim.

### Langkah Penanganan:
1. Periksa status worker:
   ```bash
   sudo systemctl status sikomando-worker
   ```
2. Restart worker:
   ```bash
   sudo systemctl restart sikomando-worker
   ```
3. Periksa antrean pekerjaan yang gagal:
   ```bash
   php artisan queue:failed
   ```

---

## 5. Cache Konflik Pasca Pembaruan Kode (Deploy)

### Gejala:
Route baru mengembalikan 404 atau konfigurasi baru tidak terbaca.

### Langkah Penanganan:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

