import React from 'react';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';

/**
 * Standard Pagination component wired with Laravel API Pagination Meta
 */
export function Pagination({ meta, onPageChange, className = '' }) {
  if (!meta || meta.total <= meta.per_page) return null;

  const { current_page = 1, last_page = 1, total = 0, from = 1, to = 1 } = meta;

  const pages = [];
  const delta = 2;
  const rangeLeft = current_page - delta;
  const rangeRight = current_page + delta;

  for (let i = 1; i <= last_page; i++) {
    if (i === 1 || i === last_page || (i >= rangeLeft && i <= rangeRight)) {
      pages.push(i);
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...');
    }
  }

  return (
    <div className={`flex flex-col sm:flex-row items-center justify-between gap-4 py-4 px-2 text-xs text-slate-600 ${className}`}>
      <div>
        Menampilkan <span className="font-semibold text-slate-800">{from || 0}</span> sampai{' '}
        <span className="font-semibold text-slate-800">{to || 0}</span> dari{' '}
        <span className="font-semibold text-slate-800">{total}</span> data
      </div>

      <div className="flex items-center gap-1 select-none">
        <button
          type="button"
          onClick={() => onPageChange(current_page - 1)}
          disabled={current_page <= 1}
          className="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-slate-600 transition"
          aria-label="Halaman Sebelumnya"
        >
          <ChevronLeftIcon className="w-4 h-4" />
        </button>

        {pages.map((p, idx) => {
          if (p === '...') {
            return (
              <span key={`dots-${idx}`} className="px-2 py-1 text-slate-400 font-medium">
                ...
              </span>
            );
          }
          const isActive = p === current_page;
          return (
            <button
              key={p}
              type="button"
              onClick={() => onPageChange(p)}
              className={`min-w-8 h-8 px-2 rounded-lg text-xs font-semibold transition cursor-pointer ${
                isActive
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
              }`}
            >
              {p}
            </button>
          );
        })}

        <button
          type="button"
          onClick={() => onPageChange(current_page + 1)}
          disabled={current_page >= last_page}
          className="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-slate-600 transition"
          aria-label="Halaman Berikutnya"
        >
          <ChevronRightIcon className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
}

export default Pagination;

