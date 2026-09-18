# Dokumentasi REST API SIKOMANDO

SIKOMANDO (**Sistem Informasi Komprehensif Manajemen Digitalisasi Hibah Organisasi**) menyediakan REST API versi 1 (`/api/v1`) yang aman, terdokumentasi, dan terstandarisasi.

---

## 1. Standar Format Respons API

Setiap endpoint API SIKOMANDO mengembalikan envelope JSON yang seragam:

### Respons Sukses (HTTP 200 / 201)
```json
{
  "success": true,
  "message": "Operasi berhasil dijalankan.",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 68
  }
}
```

### Respons Error Validasi (HTTP 422)
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "errors": {
    "email": [
      "The email field must be a valid email address."
    ]
  }
}
```

### Respons Autentikasi Diperlukan (HTTP 401)
```json
{
  "success": false,
  "message": "Autentikasi diperlukan."
}
```

### Respons Hak Akses Ditolak (HTTP 403)
```json
{
  "success": false,
  "message": "Anda tidak memiliki izin untuk melakukan tindakan ini."
}
```

### Respons Data Tidak Ditemukan (HTTP 404)
```json
{
  "success": false,
  "message": "Data yang diminta tidak ditemukan."
}
```

### Respons Kesalahan Server (HTTP 500)
```json
{
  "success": false,
  "message": "Terjadi kesalahan pada server."
}
```

---

## 2. Header Penting

- `Authorization: Bearer <personal_access_token>`: Token Sanctum wajib untuk endpoint internal.
- `Accept: application/json`: Memastikan respon selalu berformat JSON.
- `X-Request-ID`: ID penelusuran (Correlation ID) unik untuk setiap request. Jika tidak dikirim oleh client, server akan men-generate UUIDv4 baru dan menyertakannya pada header respon.

---

## 3. Matriks Role & Hak Akses (RBAC)

| Role Code | Deskripsi Peran | Batasan Akses Kunci |
|---|---|---|
| `SUPER_ADMIN` | Administrator Tunggal Sistem | Hak akses tak terbatas (Gate bypass), manajemen user, aktivasi akun, reset password. |
| `ADMIN_SIKOMANDO` | Administrator Operasional Hibah | Penugasan petugas, manajemen program hibah, verifikasi awal, dashboard internal. |
| `VERIFIKATOR` | Petugas Verifikasi Administrasi | Verifikasi kelengkapan berkas proposal, permohonan revisi berkas. |
| `EVALUATOR` | Petugas Penilaian Kelayakan | Penilaian kriteria kelayakan proposal, pemberian skor evaluasi. |
| `SURVEYOR` | Petugas Survei Lapangan | Pemeriksaan fakta lapangan, upload berita acara & temuan fisik. |
| `APPROVER` | Pejabat Penyetuju & Pembuat Keputusan | Persetujuan rekomendasi, pengesahan Surat Keputusan (SK) hibah. |
| `AUDITOR` | Pengawas Independen & Pemeriksa | Read-only ke seluruh proses bisnis, telaah audit log, preview ranking. |
| `PEMOHON` | Pengurus Organisasi Pemohon | Pengajuan usulan proposal, upload berkas, perbaikan revisi, LPJ. Dilarang melihat data/petugas internal. |

---

## 4. Daftar Modul & Ringkasan Endpoint

1. **Autentikasi**:
   - `POST /api/v1/auth/login` (Rate limited: 5 req/menit per email+IP)
   - `GET /api/v1/auth/me`
   - `POST /api/v1/auth/logout`
2. **Public Portal** (Akses terbuka tanpa login):
   - `GET /api/v1/public/grant-programs` & detail
   - `GET /api/v1/public/grant-programs/{id}/timeline`
   - `GET /api/v1/public/grant-programs/{id}/documents`
   - `GET /api/v1/public/announcements` & detail
   - `GET /api/v1/public/statistics` & summary
   - `GET /api/v1/public/transparency` & detail
3. **User Management** (Super Admin):
   - `GET /api/v1/users`
   - `POST /api/v1/users`
   - `GET /api/v1/users/by-role/{roleCode}`
   - `GET /api/v1/users/{user}`
   - `PATCH /api/v1/users/{user}`
   - `POST /api/v1/users/{user}/toggle-active`
   - `POST /api/v1/users/{user}/assign-role`
   - `POST /api/v1/users/{user}/remove-role`
   - `POST /api/v1/users/{user}/reset-password`
4. **Penugasan Petugas (Assignments)**:
   - `GET /api/v1/assignments`
   - `POST /api/v1/assignments`
   - `GET /api/v1/assignments/my`
   - `GET /api/v1/assignments/workload`
   - `POST /api/v1/assignments/{id}/revoke`
   - `GET /api/v1/proposals/{id}/assignments`
5. **Proposal & Workflow**:
   - `GET|POST /api/v1/proposals`
   - `POST /api/v1/proposals/{id}/submit`
   - `POST /api/v1/proposals/{id}/revisions`
   - `POST /api/v1/proposals/{id}/verifications`
   - `POST /api/v1/proposals/{id}/evaluations`
   - `POST /api/v1/proposals/{id}/field-surveys`
   - `POST /api/v1/grant-programs/{id}/rankings/generate`
   - `POST /api/v1/proposals/{id}/approvals`
   - `POST /api/v1/proposals/{id}/decisions`
   - `POST /api/v1/proposals/{id}/disbursements`
   - `POST /api/v1/proposals/{id}/lpj`
6. **Generator Dokumen PDF**:
   - `GET /api/v1/pdf/proposals/{id}`
   - `GET /api/v1/pdf/proposals/{id}/verifications/{vId}`
   - `GET /api/v1/pdf/proposals/{id}/evaluations/{eId}`
   - `GET /api/v1/pdf/proposals/{id}/field-surveys/{sId}`
   - `GET /api/v1/pdf/decisions/{id}`
   - `GET /api/v1/pdf/disbursements/{id}`
   - `GET /api/v1/pdf/lpj/{id}`
7. **Audit Trail & Monitoring Internal**:
   - `GET /api/v1/internal/audit-logs`
   - `GET /api/v1/internal/audit-logs/{id}`
   - `GET /api/v1/internal/dashboard/workload`
   - `GET /api/v1/internal/dashboard/tasks`
   - `GET /api/v1/internal/dashboard/assignment-history`
8. **Tata Kelola Kebijakan (Policy Governance)**:
   - `GET /api/v1/policy-configurations`
   - `GET /api/v1/policy-configurations/{code}/versions`
   - `POST /api/v1/policy-configurations/{code}/versions`
   - `POST /api/v1/policy-configurations/versions/{id}/approve` (Maker-Checker)
   - `POST /api/v1/policy-configurations/versions/{id}/activate`
9. **Kesehatan Sistem**:
   - `GET /api/health` & `GET /api/v1/health`

