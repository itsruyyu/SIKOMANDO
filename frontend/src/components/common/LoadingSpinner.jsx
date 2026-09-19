import React from 'react';

export default function LoadingSpinner({ text = 'Memuat data...', size = 'md' }) {
  const sizeClasses = {
    sm: 'h-4 w-4 border-2',
    md: 'h-8 w-8 border-3',
    lg: 'h-12 w-12 border-4',
  }[size] || 'h-8 w-8 border-3';

  return (
    <div className="flex flex-col items-center justify-center py-10 text-slate-500">
      <div
        className={`animate-spin rounded-full border-blue-600 border-t-transparent ${sizeClasses}`}
        role="status"
        aria-label="Loading"
      />
      {text && <p className="mt-3 text-sm font-medium text-slate-600">{text}</p>}
    </div>
  );
}

