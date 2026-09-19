import React from 'react';
import { FolderOpenIcon } from '@heroicons/react/24/outline';

export default function EmptyState({
  title = 'Belum ada data',
  description = 'Data tidak ditemukan atau belum pernah ditambahkan.',
  actionLabel,
  onAction,
  icon: Icon = FolderOpenIcon,
}) {
  return (
    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-12 text-center">
      <div className="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
        <Icon className="h-6 w-6" />
      </div>
      <h3 className="mt-4 text-base font-semibold text-slate-800">{title}</h3>
      <p className="mt-1 max-w-sm text-sm text-slate-500">{description}</p>
      {actionLabel && onAction && (
        <button
          type="button"
          onClick={onAction}
          className="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
        >
          {actionLabel}
        </button>
      )}
    </div>
  );
}

