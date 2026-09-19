import React from 'react';

/**
 * Standard spinner indicator for inline or full screen loading
 */
export function Spinner({ size = 'md', className = '', label = 'Memuat data...' }) {
  const sizes = {
    sm: 'w-4 h-4 border-2',
    md: 'w-8 h-8 border-3',
    lg: 'w-12 h-12 border-4',
  };

  return (
    <div className={`flex flex-col items-center justify-center gap-3 p-4 ${className}`}>
      <div
        className={`${sizes[size] || sizes.md} border-blue-600 border-t-transparent rounded-full animate-spin`}
      />
      {label && <span className="text-xs font-medium text-slate-500">{label}</span>}
    </div>
  );
}

export default Spinner;

