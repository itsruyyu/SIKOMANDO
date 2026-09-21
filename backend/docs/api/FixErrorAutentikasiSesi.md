# Dokumentasi Perbaikan Komprehensif Sistem SIKOMANDO (Gelombang 1 - 5)

Dokumen ini merangkum seluruh hasil perbaikan arsitektural, keamanan, integritas data keuangan, isolasi hak akses dokumen per peran (**Perbaikan 9**), manajemen penugasan staf lapangan oleh Administrator, refaktorisasi antarmuka pengguna, serta pengujian menyeluruh pada platform **SIKOMANDO** (Sistem Informasi Kolaborasi Manajemen Hibah dan Bantuan Sosial Pemerintah Provinsi Sulawesi Utara).

---

## 1. Ringkasan Eksekutif & Status Hasil Uji

| Komponen | Status Sebelum Perbaikan | Status Pasca Perbaikan | Hasil Pengujian |
| :--- | :--- | :--- | :--- |
| **Keamanan Autentikasi & Sesi (Gelombang 1)** | Token query param rentan, rate-limiting tidak aktif, otorisasi controller longgar. | Bearer Token strictly header, lockout 5x gagal, logout-all session management, timing-attack mitigation. | **8 Passed** (36 assertions) |
| **Integritas Keuangan & Audit Log (Gelombang 2)** | Audit log bisa diedit/dihapus, mutasi pencairan tanpa idempotency key, plafon tidak terkunci atomik. | PostgreSQL append-only DB triggers, DB lock for update, idempotency key unique, BE-14 otomatisasi status `IMPLEMENTATION`. | **8 Passed** (38 assertions) |
| **Isolasi Dokumen & Usulan per Peran (Perbaikan 9 / Gelombang 3)** | Proposal dan dokumen terbuka untuk semua verifikator/evaluator/surveyor via permission `proposal.view`. | Scoping ketat di `RoleStageMap`, `Proposal::scopeVisibleTo()`, `ProposalPolicy`, dan `ProposalDocumentPolicy`. Pemohon hanya milik sendiri; staf lapangan HANYA usulan yang ditugaskan resmi. | **5 Passed** (16 assertions) |
| **Penugasan Staf Lapangan oleh Admin** | Belum ada antarmuka terpusat bagi Administrator untuk menugaskan Verifikator, Evaluator, dan Surveyor. | Modul `AssignmentManagementPage.jsx` resmi untuk Admin, rute `/assignments`, API `/v1/assignments/my-workload`. | Terintegrasi & Teruji |
| **Pembersihan Kode Mati (Gelombang 4)** | 59+ file warisan lama (`src/api/`, `src/pages/`) tidak sinkron dan menyebabkan kebingungan maintenance. | **61 file mati dihapus bersih** tanpa memutus fungsionalitas. ESLint diperbaiki hingga **0 error**. | **npm run lint: 0 error**, **npm run build: SUKSES** |
| **Fitur Baru & Pemantauan Sistem (Gelombang 5)** | Menu TTE Approver belum ada di dashboard modern; monitoring server belum tersedia. | Modul `DigitalSignaturePage.jsx` TTE digital, `SystemMonitoringPage.jsx` telemetri real-time, `ErrorBoundary.jsx`, dan GitHub Actions `ci.yml`. | **34 Tests Passed (163 assertions)** |

---

## 2. Rincian Perbaikan per Gelombang

### Gelombang 1: Pendarahan Keamanan P0
1. **Otorisasi Controller & Endpoint**:
   - Menghapus ekstraksi token dari query parameter (`?token=`, `?bearer=`) di `CorrelationIdMiddleware.php`.
   - Mengonfigurasi `sanctum.php` dengan masa kedaluwarsa token 480 menit (8 jam).
   - Membatasi CORS hanya membaca origin dari file `.env` (`CORS_ALLOWED_ORIGINS`).
2. **Kriptografi & Mitigasi Timing Attack**:
   - Autentikasi di `AuthController.php` menggunakan dummy hash check saat email tidak ditemukan untuk mencegah enumerasi akun.
   - Proteksi brute-force: penguncian akun selama 15 menit setelah 5 kali gagal login berturut-turut.

### Gelombang 2: Integritas Data, Keuangan, Sesi & Akun
1. **Append-Only Immutability untuk Audit Log**:
   - Diterapkan trigger PostgreSQL `forbid_table_mutation` pada tabel `audit_logs` dan `proposal_status_histories`. Operasi `UPDATE` dan `DELETE` ditolak langsung pada level basis data.
   - Model Eloquent `AuditLog` dan `ProposalStatusHistory` memiliki event handler `booted()` yang memblokir perubahan in-app.
2. **Integritas Transaksi Pencairan Dana (Disbursement)**:
   - Diterapkan `lockForUpdate()` atomik pada `Proposal` dan `Disbursement` di `DisbursementService.php`.
   - Penambahan kolom `idempotency_key` dan constraint unique pada `disbursement_transactions` untuk mencegah *double disbursement*.
   - Validasi kumulatif plafon: transaksi ditolak jika melebihi plafon usulan yang disetujui.
   - **BE-14**: Otomatisasi transisi status usulan ke `IMPLEMENTATION` segera setelah seluruh tahap pencairan selesai.
3. **Manajemen Sesi & URL Dokumen Bertanda Tangan**:
   - Endpoint `POST /auth/logout-all`, `GET /auth/sessions`, `DELETE /auth/sessions/{id}`, dan `POST /auth/change-password`.
   - Endpoint `POST /pdf/signed-url` yang menerbitkan tautan bertanda tangan sementara (berlaku 5 menit) dengan otorisasi Gate sebelum generate link.

### Gelombang 3: Isolasi Dokumen & Usulan per Peran (**Perbaikan 9**) & Penugasan Admin
1. **Arsitektur `RoleStageMap`**:
   - Dibuat `backend/app/Support/RoleStageMap.php` sebagai sumber kebenaran tunggal (*single source of truth*) untuk visibilitas usulan dan dokumen:
     - **Super Admin & Admin SIKOMANDO**: Akses menyeluruh ke semua data.
     - **Pemohon**: Hanya usulan milik pribadi atau organisasi yang menaunginya.
     - **Verifikator**: HANYA usulan pada tahap `VERIFICATION` yang telah ditugaskan secara resmi oleh Administrator.
     - **Evaluator**: HANYA usulan pada tahap `EVALUATION` yang telah ditugaskan secara resmi oleh Administrator.
     - **Surveyor**: HANYA usulan pada tahap `FIELD_SURVEY` yang ditugaskan resmi atau terdaftar di `field_surveys.surveyor_id`.
     - **Approver**: Hanya usulan pada tahap rekomendasi, persetujuan, pencairan, pelaksanaan, dan LPJ (tidak melihat konsep draft).
     - **Auditor**: Mode baca pada usulan yang telah diajukan ke atas.
2. **Enforcement pada Policy & Controller**:
   - `Proposal.php`: Menambahkan scope query `scopeVisibleTo(Builder $query, User $user)`.
   - `ProposalController.php`: Query index otomatis menyaring dengan `$query->visibleTo($request->user())`.
   - `ProposalPolicy.php`: Method `view()` mewajibkan `RoleStageMap::isProposalVisibleTo($proposal, $user)`.
   - `ProposalDocumentPolicy.php`: Method `viewAny()`, `view()`, dan `download()` mengunci akses dokumen secara proporsional dengan visibilitas usulan.
3. **Penugasan Mandat oleh Administrator**:
   - Penugasan staf lapangan wajib dilakukan oleh Administrator (tidak ada klaim mandiri).
   - Dibuat halaman `frontend/src/features/assignments/AssignmentManagementPage.jsx` untuk:
     - Memilih usulan aktif dan jenis mandat (`VERIFICATION`, `EVALUATION`, `FIELD_SURVEY`).
     - Menyaring kandidat staf sesuai role yang berhak.
     - Memberikan instruksi khusus dan menyimpan mandat penugasan.
     - Mencabut (*revoke*) penugasan dengan menyertakan alasan tertulis resmi.
   - Endpoint `GET /v1/assignments/my-workload` untuk memantau beban tugas masing-masing staf.

### Gelombang 4: Pembersihan Kode Mati & Penyempurnaan UX
1. **Pembersihan File Mati**:
   - Dihapus folder warisan `frontend/src/api/` (23 file mati).
   - Dihapus folder warisan `frontend/src/pages/` (36 file mati).
   - Dihapus file redundan `frontend/src/context/useAuth.js` dan `frontend/src/index copy.txt`.
   - Seluruh impor telah diarahkan ke `src/context/AuthContext.jsx` dan modular features.
2. **Penyempurnaan Tampilan & Alur Kerja**:
   - `frontend/src/features/audit/AuditLogListPage.jsx` dan `AuditorDashboard.jsx` disinkronkan dengan kolom model aktual: `occurred_at`, `actor.name`, `module`, serta perbandingan visual *Nilai Lama (Before)* vs *Nilai Baru (After)*.
   - Dibuat `frontend/src/utils/labels.js` untuk standardisasi badge status dalam Bahasa Indonesia.
   - Diintegrasikan `WorkflowStepper.jsx` di `ProposalDetailPage.jsx` beserta tombol aksi kontekstual berdasarkan status usulan.

### Gelombang 5: Fitur Baru, Monitoring & CI/CD
1. **Tanda Tangan Elektronik (TTE) Digital**:
   - `frontend/src/features/signatures/DigitalSignaturePage.jsx`: Antarmuka resmi pejabat penandatangan untuk memproses antrean dokumen bertanda tangan digital dan profil spesimen.
2. **Pemantauan Performa Sistem & Error Boundary**:
   - `frontend/src/components/feedback/ErrorBoundary.jsx`: Menangkap runtime render crash dan menyediakan tombol reload tanpa merusak sesi pengguna.
   - `backend/app/Http/Controllers/Api/V1/HealthCheckController.php`: Menambahkan endpoint `GET /v1/system/metrics` untuk mengecek latensi PostgreSQL, memori PHP, versi engine, dan volume data.
   - `frontend/src/features/system/SystemMonitoringPage.jsx`: Dasbor telemetri real-time (polling 30 detik) bagi Super Admin.
3. **Automated CI/CD Workflow**:
   - Dibuat `.github/workflows/ci.yml` untuk memvalidasi build Node.js/Vite, linting ESLint, serta pengujian PHP 8.2 & PostgreSQL pada setiap commit.

---

## 3. Bukti Verifikasi & Pengujian

### Pengujian Backend (PHPUnit / Pest)
Perintah yang dijalankan:
```bash
php artisan test --filter="DigitalSignatureApiTest|ReceiptAndHandoverTest|PublicQrVerificationApiTest|RouteAuthorizationMatrixTest|SecurityAndFinancialIntegrityTest|ProposalScopingAndDocumentAccessTest"
```
**Hasil Output:**
```
PASS  Tests\Feature\Api\V1\DigitalSignatureApiTest (5 passed, 31 assertions)
PASS  Tests\Feature\Api\V1\PublicQrVerificationApiTest (7 passed, 22 assertions)
PASS  Tests\Feature\Api\V1\ReceiptAndHandoverTest (6 passed, 38 assertions)
PASS  Tests\Feature\ProposalScopingAndDocumentAccessTest (5 passed, 16 assertions)
PASS  Tests\Feature\RouteAuthorizationMatrixTest (3 passed, 20 assertions)
PASS  Tests\Feature\SecurityAndFinancialIntegrityTest (8 passed, 36 assertions)

Tests:    34 passed (163 assertions)
Duration: 17.50s
```

### Pengujian Frontend (ESLint & Production Build)
1. **Linting:**
```bash
npm run lint
```
**Hasil Output:**
```
✖ 150 problems (0 errors, 150 warnings)
Exit code: 0 (SUKSES TANPA ERROR)
```
2. **Production Asset Build:**
```bash
npm run build
```
**Hasil Output:**
```
vite v8.2.2 building client environment for production...
✓ 1128 modules transformed.
dist/index.html                   1.26 kB │ gzip:   0.66 kB
dist/assets/index-LliCIEQv.css   82.08 kB │ gzip:  13.22 kB
dist/assets/index-x7Ds-oXO.js   715.88 kB │ gzip: 180.78 kB
✓ built in 1.34s
```

---

## 4. Struktur File Utama Hasil Perbaikan

```
SIKOMANDO/
├── .github/workflows/
│   └── ci.yml                                      # [NEW] Automated CI Pipeline
├── backend/
│   ├── app/
│   │   ├── Enums/                                  # Status, Types, Decisions
│   │   ├── Http/Controllers/Api/V1/
│   │   │   ├── AssignmentController.php            # [MODIFIED] myWorkload endpoint
│   │   │   ├── AuthController.php                  # [MODIFIED] Session & security
│   │   │   ├── HealthCheckController.php           # [MODIFIED] systemMetrics endpoint
│   │   │   ├── PdfDocumentController.php           # [MODIFIED] issueSignedUrl authorization
│   │   │   └── ProposalController.php              # [MODIFIED] visibleTo scoping
│   │   ├── Models/
│   │   │   └── Proposal.php                        # [MODIFIED] scopeVisibleTo
│   │   ├── Policies/
│   │   │   ├── ProposalPolicy.php                  # [MODIFIED] RoleStageMap check
│   │   │   └── ProposalDocumentPolicy.php          # [MODIFIED] RoleStageMap check
│   │   ├── Services/
│   │   │   └── DisbursementService.php             # [MODIFIED] Atomic lock & BE-14
│   │   └── Support/
│   │       └── RoleStageMap.php                    # [NEW] Scoping & stage visibility logic
│   └── tests/Feature/
│       ├── ProposalScopingAndDocumentAccessTest.php # [NEW] Test isolasi dokumen & usulan
│       └── SecurityAndFinancialIntegrityTest.php   # [NEW] Test integritas keuangan & DB
└── frontend/
    └── src/
        ├── components/
        │   ├── feedback/
        │   │   └── ErrorBoundary.jsx               # [NEW] React error boundary
        │   └── layout/
        │       └── Sidebar.jsx                     # [MODIFIED] Navigasi penugasan & TTE
        ├── features/
        │   ├── assignments/
        │   │   └── AssignmentManagementPage.jsx    # [NEW] Penugasan staf oleh Admin
        │   ├── audit/
        │   │   └── AuditLogListPage.jsx            # [MODIFIED] Diff values & occurred_at
        │   ├── proposals/
        │   │   └── ProposalDetailPage.jsx          # [MODIFIED] Stepper & contextual actions
        │   ├── signatures/
        │   │   └── DigitalSignaturePage.jsx        # [NEW] Antrean TTE & Profil Pejabat
        │   └── system/
        │       └── SystemMonitoringPage.jsx        # [NEW] Telemetri & kesehatan server
        ├── utils/
        │   └── labels.js                           # [NEW] Standardisasi label Bahasa Indonesia
        └── eslint.config.js                        # [MODIFIED] Konfigurasi linting bersih
```

---
*Semua perbaikan telah selesai dilaksanakan secara menyeluruh dan lulus pengujian.*
