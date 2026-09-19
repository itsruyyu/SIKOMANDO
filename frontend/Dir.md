frontend/src/features/
├── public-portal/           # Portal Publik Tanpa Login
│   ├── LandingPage.jsx      # Hero, 3 Logo, Tracking Cepat, Stat Dropdown Tahun, Alur 6 Tahap
│   ├── ProgramsPage.jsx     # Katalog Program Hibah Aktif dari DB
│   ├── ProgramDetailPage.jsx# Detail Syarat, Jadwal, Dokumen Program
│   ├── TrackerPage.jsx      # Pelacakan Status Usulan Publik
│   ├── StatisticsPage.jsx   # Visualisasi Serapan Anggaran & Filter Tahun
│   ├── TransparencyPage.jsx # Daftar Terbuka Penerima Hibah Pemprov Sulut
│   └── VerifyQrPage.jsx     # Verifikasi Publik Token QR Kriptografi
├── auth/
│   └── LoginPage.jsx        # Login Sanctum + Quick Switcher 8 Role Akun Demo
├── dashboard/
│   ├── DashboardRouter.jsx  # Router Adaptif berdasarkan Role Pengguna Aktif
│   ├── PemohonDashboard.jsx # Dashboard Ormas: Ringkasan Usulan, Revisi, Pencairan
│   ├── InternalStaffDashboard.jsx # Verifikator, Evaluator, Surveyor: Beban Kerja & Antrean Tugas
│   ├── ApproverDashboard.jsx# TAPD & Gubernur: Ringkasan Rekomendasi, SK & Anggaran
│   ├── AuditorDashboard.jsx # Inspektorat/BPK: Log Audit, Realisasi, Integritas Berkas
│   └── AdminDashboard.jsx   # Admin Sistem: Statistik Pengguna, Konfigurasi, Server Health
├── proposals/
│   ├── ProposalListPage.jsx # Tabel Usulan dengan Filter Status, Search, dan Paginasi
│   ├── ProposalWizardPage.jsx # 5-Step Submission Wizard (Profil, Kegiatan, RAB, Berkas, Final Review)
│   ├── ProposalDetailPage.jsx # Dossier Detail Usulan + Riwayat Verifikasi & Pencairan
│   └── components/
│       ├── RabBuilder.jsx   # Form Penyusunan Rencana Anggaran Biaya Real-time
│       └── RevisionModal.jsx# Form Tanggapan Revisi Berkas Pemohon
├── verifications/
│   ├── VerificationListPage.jsx # Antrean Usulan Siap Verifikasi Administrasi
│   └── VerificationWorkspacePage.jsx # Split-screen Berkas Checklist & Catatan Kelayakan
├── evaluations/
│   ├── EvaluationListPage.jsx # Antrean Usulan Siap Evaluasi Substantif
│   └── EvaluationWorkspacePage.jsx # Lembar Penilaian Rubrik Berbobot Kebijakan
├── surveys/
│   ├── FieldSurveyListPage.jsx # Antrean Survei Lapangan Lapangan
│   └── FieldSurveyWorkspacePage.jsx # Form Lapangan: Kunci Koordinat GPS, Bukti Foto, Catatan
├── recommendations/
│   └── RankingRecommendationPage.jsx # Kalkulasi & Pemeringkatan Usulan TAPD
├── approvals/
│   ├── ApprovalListPage.jsx # Antrean Persetujuan Pejabat
│   └── ExecutiveDossierPage.jsx # Dossier Eksekutif & Keputusan 1-Klik
├── decisions/
│   ├── DecisionListPage.jsx # Daftar Surat Keputusan (SK) Gubernur
│   └── DecisionDetailPage.jsx # Tampilan SK Resmi Berkop Garuda/Pemprov Sulut + Unduh PDF
├── disbursements/
│   ├── DisbursementListPage.jsx # Monitoring SP2D & Pencairan Dana
│   └── DisbursementDetailPage.jsx # Detail Rekening Bank SulutGo, SPM, dan Status Transfer
├── realizations/
│   ├── RealizationListPage.jsx # Daftar Paket Belanja Hibah
│   └── RealizationDetailPage.jsx # Form Bukti Kwitansi, Item Belanja, & Berita Acara (BAST)
├── lpj/
│   ├── LpjListPage.jsx      # Daftar Laporan Pertanggungjawaban
│   └── LpjDetailPage.jsx    # Rekonsiliasi Anggaran vs Realisasi, Penutupan Usulan
├── audit/
│   └── AuditLogListPage.jsx # Audit Trail Lengkap dengan Perbandingan JSON Diff
├── users/
│   └── UserManagementPage.jsx # Manajemen Pengguna, Penugasan Role, Reset Password, Toggle Aktif
├── policies/
│   └── PolicyConfigurationPage.jsx # Tata Kelola Maker-Checker Kebijakan & Parameter Rubrik
├── qr/
│   └── QrManagementPage.jsx # Resolusi Token QR Kriptografi, Pencabutan (Revoke), & Log Scan
└── notifications/
    └── NotificationInboxPage.jsx # Kotak Masuk Notifikasi Real-time & Navigasi Berkas