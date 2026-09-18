# Checklist Kesiapan Produksi SIKOMANDO

Daftar periksa verifikasi akhir sebelum merilis backend SIKOMANDO ke lingkungan produksi.

---

## 1. Keamanan & Konfigurasi Lingkungan
- [ ] `APP_ENV=production` disetel di `.env`.
- [ ] `APP_DEBUG=false` disetel (mencegah kebocoran stack trace dan kredensial).
- [ ] `APP_KEY` telah digenerate secara acak dan unik.
- [ ] Password database menggunakan kredensial yang kuat (bukan default/password bawaan dev).
- [ ] Kunci rahasia JWT / Sanctum tidak terekspos ke repositori git.
- [ ] Rate limiting login aktif (5 upaya per menit per IP + email).
- [ ] Header keamanan aktif (`X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`).
- [ ] User nonaktif diblokir secara otomatis dari penggunaan token API via `EnsureUserIsActive`.
- [ ] Super Admin tunggal terlindungi (tidak dapat dinonaktifkan atau dihapus).

---

## 2. Optimasi Kinerja & Cache Laravel
Jalankan perintah optimasi sebelum melayani traffic:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```
*Catatan: Pastikan `php artisan optimize:clear` dijalankan jika ada perubahan file konfigurasi atau rute.*

---

## 3. Penyimpanan & Izin Berkas
- [ ] Direktori `storage/` dan `bootstrap/cache/` memiliki izin tulis untuk user web server:
  ```bash
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
  ```
- [ ] Symlink storage publik telah dibuat (`php artisan storage:link`).
- [ ] Disk `private` untuk dokumen sensitif internal tidak dapat diakses langsung via URL web publik.

---

## 4. Pemantauan & Observability
- [ ] Endpoint health check `/api/health` dan `/api/v1/health` dapat diakses oleh monitoring tool (UptimeRobot, Datadog, Prometheus).
- [ ] Logging dikonfigurasi ke log level `info` atau `warning` dengan rotasi log (`LOG_STACK=daily` atau syslog).
- [ ] Request ID (`X-Request-ID`) terekam pada header dan file log.
- [ ] Masking otomatis pada data sensitif (password, token, rekening bank) aktif.

---

## 5. Rencana Kontingensi & Rollback
- [ ] Salinan cadangan (backup) database dibuat sebelum proses rilis atau migrasi baru.
- [ ] Tersedia prosedur rollback cepat jika migrasi gagal:
  ```bash
  php artisan migrate:rollback --step=1
  ```
- [ ] Mode pemeliharaan dapat diaktifkan dalam kondisi darurat:
  ```bash
  php artisan down --secret="bypass-token-here"
  ```
- [ ] Mode pemeliharaan dinonaktifkan setelah perbaikan selesai:
  ```bash
  php artisan up
  ```

