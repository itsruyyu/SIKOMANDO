import React from 'react';

const STATUS_CONFIG = {
  draft: {
    label: 'Draft',
    bg: 'bg-slate-100',
    text: 'text-slate-700',
    border: 'border-slate-200',
  },
  submitted: {
    label: 'Diajukan',
    bg: 'bg-blue-50',
    text: 'text-blue-700',
    border: 'border-blue-200',
  },
  verification: {
    label: 'Verifikasi Administrasi',
    bg: 'bg-amber-50',
    text: 'text-amber-700',
    border: 'border-amber-200',
  },
  revision: {
    label: 'Perlu Revisi',
    bg: 'bg-orange-50',
    text: 'text-orange-700',
    border: 'border-orange-200',
  },
  verified: {
    label: 'Terverifikasi',
    bg: 'bg-cyan-50',
    text: 'text-cyan-700',
    border: 'border-cyan-200',
  },
  evaluation: {
    label: 'Evaluasi Teknis',
    bg: 'bg-indigo-50',
    text: 'text-indigo-700',
    border: 'border-indigo-200',
  },
  survey: {
    label: 'Survei Lapangan',
    bg: 'bg-purple-50',
    text: 'text-purple-700',
    border: 'border-purple-200',
  },
  recommended: {
    label: 'Direkomendasikan',
    bg: 'bg-teal-50',
    text: 'text-teal-700',
    border: 'border-teal-200',
  },
  approval: {
    label: 'Menunggu Persetujuan',
    bg: 'bg-yellow-50',
    text: 'text-yellow-800',
    border: 'border-yellow-200',
  },
  approved: {
    label: 'Disetujui',
    bg: 'bg-emerald-50',
    text: 'text-emerald-700',
    border: 'border-emerald-200',
  },
  rejected: {
    label: 'Ditolak',
    bg: 'bg-rose-50',
    text: 'text-rose-700',
    border: 'border-rose-200',
  },
  disbursed: {
    label: 'Dana Disalurkan',
    bg: 'bg-emerald-100',
    text: 'text-emerald-800',
    border: 'border-emerald-300',
  },
  implementation: {
    label: 'Pelaksanaan',
    bg: 'bg-blue-100',
    text: 'text-blue-800',
    border: 'border-blue-300',
  },
  lpj_submitted: {
    label: 'LPJ Diajukan',
    bg: 'bg-sky-50',
    text: 'text-sky-700',
    border: 'border-sky-200',
  },
  lpj_verified: {
    label: 'LPJ Diverifikasi',
    bg: 'bg-teal-100',
    text: 'text-teal-800',
    border: 'border-teal-300',
  },
  completed: {
    label: 'Selesai',
    bg: 'bg-green-100',
    text: 'text-green-800',
    border: 'border-green-300',
  },
  cancelled: {
    label: 'Dibatalkan',
    bg: 'bg-gray-100',
    text: 'text-gray-600',
    border: 'border-gray-200',
  },
};

export default function StatusBadge({ status, customLabel, size = 'md' }) {
  const normalizedKey = String(status || '').toLowerCase();
  const config = STATUS_CONFIG[normalizedKey] || {
    label: customLabel || status || 'Tidak Diketahui',
    bg: 'bg-slate-100',
    text: 'text-slate-600',
    border: 'border-slate-200',
  };

  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-xs font-medium',
    lg: 'px-3 py-1.5 text-sm font-semibold',
  }[size] || 'px-2.5 py-1 text-xs font-medium';

  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full border ${config.bg} ${config.text} ${config.border} ${sizeClasses}`}
    >
      <span className="h-1.5 w-1.5 rounded-full bg-current opacity-75" />
      {customLabel || config.label}
    </span>
  );
}

