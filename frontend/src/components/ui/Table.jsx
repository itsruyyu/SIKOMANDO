import React from 'react';

/**
 * Standard Data Table wrapper with clean styling, responsive overflow, and empty state support
 */
export function Table({ children, className = '' }) {
  return (
    <div className="w-full overflow-x-auto rounded-xl border border-slate-200/80 shadow-2xs bg-white">
      <table className={`min-w-full divide-y divide-slate-200 text-left text-sm ${className}`}>
        {children}
      </table>
    </div>
  );
}

export function TableHead({ children }) {
  return <thead className="bg-slate-50/80 text-xs font-semibold text-slate-600 uppercase tracking-wider">{children}</thead>;
}

export function TableBody({ children }) {
  return <tbody className="divide-y divide-slate-100 bg-white">{children}</tbody>;
}

export function TableRow({ children, className = '', hover = true, onClick }) {
  return (
    <tr
      onClick={onClick}
      className={`transition-colors ${hover ? 'hover:bg-blue-50/40' : ''} ${
        onClick ? 'cursor-pointer' : ''
      } ${className}`}
    >
      {children}
    </tr>
  );
}

export function TableHeaderCell({ children, className = '' }) {
  return (
    <th scope="col" className={`px-4 py-3.5 whitespace-nowrap ${className}`}>
      {children}
    </th>
  );
}

export function TableCell({ children, className = '', mono = false }) {
  return (
    <td className={`px-4 py-3.5 whitespace-nowrap text-slate-700 ${mono ? 'font-mono text-xs' : ''} ${className}`}>
      {children}
    </td>
  );
}

export default Table;

