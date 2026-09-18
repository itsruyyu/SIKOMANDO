# SIKOMANDO — Manual End-to-End Business Flow Checklist

Daftar periksa pengujian manual (Manual End-to-End Test Checklist) untuk memverifikasi 23 tahapan siklus bisnis SIKOMANDO dari pendaftaran hingga penutupan proposal dan transparansi publik.

---

| No | Tahap / Skenario Pengujian | Aktor | Endpoint / Aksi | Kriteria Keberhasilan (Pass Criteria) | Status |
|---|---|---|---|---|---|
| 1 | Akses Portal Publik | Tamu (Guest) | `GET /api/v1/public/grant-programs` | Daftar program hibah aktif muncul tanpa login. Tidak ada data pribadi yang bocor. | **PASS** |
| 2 | Akses Statistik Publik | Tamu (Guest) | `GET /api/v1/public/statistics` | Rekapitulasi agregat (penerima, dana, progres) tampil konsisten. | **PASS** |
| 3 | Autentikasi Pengguna | Pemohon | `POST /api/v1/auth/login` | Token Sanctum diterbitkan, user status `is_active: true`. | **PASS** |
| 4 | Eksplorasi Program Aktif | Pemohon | `GET /api/v1/grant-programs/{id}` | Jadwal, kriteria, dan persyaratan dokumen tampil lengkap. | **PASS** |
| 5 | Pembuatan Draf Proposal | Pemohon | `POST /api/v1/proposals` | Proposal terbentuk dengan status `DRAFT`, RAB tervalidasi. | **PASS** |
| 6 | Unggah Berkas Persyaratan | Pemohon | `POST /api/v1/proposals/{id}/documents` | Berkas PDF tersimpan privat di storage, record versi dibuat. | **PASS** |
| 7 | Pengajuan Proposal | Pemohon | `POST /api/v1/proposals/{id}/submit` | Status proposal berubah menjadi `SUBMITTED`. Notifikasi terkirim. | **PASS** |
| 8 | Penugasan Verifikator | Admin | `POST /api/v1/assignments` | Verifikator ditugaskan, workload tercatat, role tervalidasi. | **PASS** |
| 9 | Verifikasi Administrasi | Verifikator | `POST /api/v1/proposals/{id}/verifications` | Item verifikasi dicentang, status menjadi `VERIFIED`. | **PASS** |
| 10 | Penilaian Teknis & Scoring | Evaluator | `POST /api/v1/proposals/{id}/evaluations` | Skor kriteria diisi, total skor terakumulasi otomatis. | **PASS** |
| 11 | Penjadwalan Survei Lapangan | Admin | `POST /api/v1/proposals/{id}/field-surveys` | Jadwal dan tim surveyor ditetapkan. | **PASS** |
| 12 | Eksekusi & Hasil Survei | Surveyor | `POST .../result` & `.../complete` | Temuan & foto survey diunggah, rekomendasi dicatat. | **PASS** |
| 13 | Kalkulasi Ranking Otomatis | Sistem / Admin | `POST .../rankings` & `.../finalize` | Bobot evaluasi & survei dihitung, urutan ranking final. | **PASS** |
| 14 | Pengajuan Persetujuan (Maker) | Admin Maker | `POST /api/v1/proposals/{id}/approvals` | Rekomendasi diajukan ke approver/pimpinan. | **PASS** |
| 15 | Persetujuan (Checker) | Approver | `POST .../approvals/{id}/approve` | Maker-checker divalidasi (maker ≠ checker), status `APPROVED`. | **PASS** |
| 16 | Penetapan Keputusan & SK | Pimpinan / Admin | `POST /api/v1/decisions` | Nomor SK dan penetapan nominal terbit, status `published`. | **PASS** |
| 17 | Pembuatan PDF SK & Proposal | Internal / Pemohon | `GET /api/v1/pdf/proposals/{id}` | Dokumen biner PDF `%PDF-` diunduh secara aman. | **PASS** |
| 18 | Rencana Pencairan Dana | Admin | `POST /api/v1/proposals/{id}/disbursement-plans` | Tahapan pencairan termin dana hibah ditetapkan. | **PASS** |
| 19 | Verifikasi & Persetujuan Pencairan | Approver | `POST .../disbursements/{id}/approve` | Pencairan tahap disetujui, siap eksekusi bayar. | **PASS** |
| 20 | Pencatatan Realisasi Bayar | Bendahara | `POST .../disbursements/{id}/transactions` | SP2D / nomor referensi bank dicatat, status `PAID`. | **PASS** |
| 21 | Pengajuan & Unggah LPJ | Pemohon | `POST /api/v1/proposals/{id}/lpj` | Laporan fisik, kuitansi, dan sisa saldo diserahkan. | **PASS** |
| 22 | Review & Persetujuan LPJ | Verifikator / Admin | `POST .../lpj/{id}/approve` | Bukti diperiksa lunas, status LPJ menjadi `APPROVED`. | **PASS** |
| 23 | Penutupan Proposal (Closing) | Admin | `POST /api/v1/proposals/{id}/close` | Status `COMPLETED`, audit trail tercatat, transparansi publik tersinkron. | **PASS** |

