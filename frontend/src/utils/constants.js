export const ROLES = {
  SUPER_ADMIN: 'SUPER_ADMIN',
  ADMIN_SIKOMANDO: 'ADMIN_SIKOMANDO',
  VERIFIKATOR: 'VERIFIKATOR',
  EVALUATOR: 'EVALUATOR',
  SURVEYOR: 'SURVEYOR',
  APPROVER: 'APPROVER',
  AUDITOR: 'AUDITOR',
  PEMOHON: 'PEMOHON',
};

export const ROLE_LABELS = {
  SUPER_ADMIN: 'Super Admin',
  ADMIN_SIKOMANDO: 'Administrator SIKOMANDO',
  VERIFIKATOR: 'Petugas Verifikasi',
  EVALUATOR: 'Evaluator Teknis',
  SURVEYOR: 'Petugas Survei Lapangan',
  APPROVER: 'Pimpinan / Pejabat Penyetuju',
  AUDITOR: 'Inspektorat / Auditor',
  PEMOHON: 'Pemohon Hibah',
};

export const ROLE_BADGE_COLORS = {
  SUPER_ADMIN: 'bg-purple-100 text-purple-800 border-purple-200',
  ADMIN_SIKOMANDO: 'bg-blue-100 text-blue-800 border-blue-200',
  VERIFIKATOR: 'bg-cyan-100 text-cyan-800 border-cyan-200',
  EVALUATOR: 'bg-indigo-100 text-indigo-800 border-indigo-200',
  SURVEYOR: 'bg-amber-100 text-amber-800 border-amber-200',
  APPROVER: 'bg-emerald-100 text-emerald-800 border-emerald-200',
  AUDITOR: 'bg-rose-100 text-rose-800 border-rose-200',
  PEMOHON: 'bg-slate-100 text-slate-800 border-slate-200',
};

export const PROPOSAL_STATUSES = {
  draft: { label: 'Draft', color: 'bg-slate-100 text-slate-700 border-slate-200' },
  submitted: { label: 'Diajukan', color: 'bg-blue-100 text-blue-800 border-blue-200' },
  verification: { label: 'Verifikasi Administrasi', color: 'bg-amber-100 text-amber-800 border-amber-200' },
  revision: { label: 'Perlu Revisi', color: 'bg-orange-100 text-orange-800 border-orange-200' },
  verified: { label: 'Lolos Administrasi', color: 'bg-teal-100 text-teal-800 border-teal-200' },
  evaluation: { label: 'Evaluasi Substantif', color: 'bg-indigo-100 text-indigo-800 border-indigo-200' },
  survey: { label: 'Survei Lapangan', color: 'bg-cyan-100 text-cyan-800 border-cyan-200' },
  recommended: { label: 'Rekomendasi TAPD', color: 'bg-violet-100 text-violet-800 border-violet-200' },
  approval: { label: 'Menunggu Persetujuan', color: 'bg-yellow-100 text-yellow-800 border-yellow-200' },
  approved: { label: 'Disetujui (SK Terbit)', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  rejected: { label: 'Ditolak', color: 'bg-red-100 text-red-800 border-red-200' },
  disbursed: { label: 'Dana Disalurkan (SP2D)', color: 'bg-blue-100 text-blue-800 border-blue-200' },
  implementation: { label: 'Pelaksanaan Kegiatan', color: 'bg-sky-100 text-sky-800 border-sky-200' },
  lpj_submitted: { label: 'LPJ Diajukan', color: 'bg-purple-100 text-purple-800 border-purple-200' },
  completed: { label: 'Selesai & Ditutup', color: 'bg-green-100 text-green-800 border-green-200' },
};

export const SULUT_REGENCIES = [
  'Kota Manado',
  'Kota Bitung',
  'Kota Tomohon',
  'Kota Kotamobagu',
  'Kabupaten Minahasa',
  'Kabupaten Minahasa Utara',
  'Kabupaten Minahasa Selatan',
  'Kabupaten Minahasa Tenggara',
  'Kabupaten Bolaang Mongondow',
  'Kabupaten Bolaang Mongondow Utara',
  'Kabupaten Bolaang Mongondow Selatan',
  'Kabupaten Bolaang Mongondow Timur',
  'Kabupaten Kepulauan Sangihe',
  'Kabupaten Kepulauan Talaud',
  'Kabupaten Kepulauan Siau Tagulandang Biaro (Sitaro)',
];

export const RAB_CATEGORIES = [
  'Bahan dan Material Kegiatan',
  'Peralatan dan Sarana Penunjang',
  'Honorarium dan Jasa Profesional',
  'Operasional dan Penyelenggaraan Acara',
  'Akomodasi dan Transportasi Lapangan',
  'Publikasi dan Dokumentasi',
  'Lain-lain',
];

export const ORGANIZATION_TYPES = [
  'Yayasan Kesejahteraan Sosial',
  'Organisasi Kemasyarakatan (Ormas)',
  'Lembaga Keagamaan / Rumah Ibadah',
  'Komunitas Seni, Budaya & Olahraga',
  'Lembaga Pemberdayaan Masyarakat',
  'Organisasi Pemuda & Mahasiswa',
];

