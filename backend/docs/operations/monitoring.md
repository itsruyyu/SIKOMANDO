# SIKOMANDO — Panduan Monitoring & Observability

Dokumen ini memandu tim operasional sistem SIKOMANDO dalam memantau kesehatan aplikasi, performa API, penelusuran audit log, dan pendeteksian dini anomali sistem.

---

## 1. Komponen Utama Monitoring
1. **Health Check Probes**:
   - URL: `/api/health` dan `/api/v1/health`
   - Target Uptime SLA: 99.9%
   - Metrik yang Diperiksa:
     - Koneksi Database PostgreSQL (`SELECT 1`, status `ok`)
     - Cache Engine (`Cache::put/get`, status `ok`)
     - Storage Filesystem (`Storage::disk('local')->exists`, status `ok`)
     - Response Latency (`< 150 ms`)
2. **Correlation ID (Request ID)**:
   - Setiap HTTP response membawa header `X-Request-ID`.
   - Log Laravel di `storage/logs/laravel-*.log` menyertakan context `request_id`.
   - Gunakan grep untuk melacak request:
     ```bash
     grep "req-uuid-dari-user" storage/logs/laravel-$(date +%Y-%m-%d).log
     ```

---

## 2. Pemantauan Log Aplikasi

### Log Rotasi Harian (`daily`)
File log otomatis dirotasi harian dengan retensi 14 hari:
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Format Log API (`ApiLoggingMiddleware`)
```text
[2026-09-18 08:20:10] local.INFO: HTTP API Request Completed {"request_id":"550e8400-e29b-41d4-a716-446655440000","method":"POST","url":"/api/v1/proposals","status":201,"duration_ms":42.5,"ip":"127.0.0.1","user_id":"a1b2c3d4-..."}
```

---

## 3. Pemantauan Antrean Kerja (Queue Monitoring)
1. **Pemeriksaan Failed Jobs**:
   ```bash
   php artisan queue:failed
   ```
2. **Retry Failed Job**:
   ```bash
   php artisan queue:retry all # atau id job spesifik
   ```
3. **Hapus Failed Job Usang**:
   ```bash
   php artisan queue:flush
   ```

---

## 4. Penelusuran Audit Trail Internal
Endpoint internal khusus auditor:
- `GET /api/v1/internal/audit-logs`
  - Filter: `?event=status_changed&auditable_type=Proposal&date_from=2026-09-01`
  - Hak Akses: `SUPER_ADMIN`, `ADMIN_SIKOMANDO`, `AUDITOR`
  - Menampilkan: Aktor, IP, aksi bisnis, nilai lama (`old_values`), dan nilai baru (`new_values`).

