import React from 'react';

/**
 * Standardized Skeleton Loaders for table rows, cards, stats widgets, and detail views
 */
export function SkeletonLoader({ type = 'row', count = 3, className = '' }) {
  if (type === 'card') {
    return (
      <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 ${className}`}>
        {Array.from({ length: count }).map((_, idx) => (
          <div key={idx} className="bg-white p-6 rounded-xl border border-slate-200 animate-pulse flex flex-col gap-4">
            <div className="flex items-center justify-between">
              <div className="h-4 bg-slate-200 rounded-sm w-1/3" />
              <div className="h-6 bg-slate-200 rounded-full w-16" />
            </div>
            <div className="h-5 bg-slate-200 rounded-sm w-3/4" />
            <div className="space-y-2 pt-2">
              <div className="h-3 bg-slate-100 rounded-sm w-full" />
              <div className="h-3 bg-slate-100 rounded-sm w-5/6" />
            </div>
            <div className="pt-4 border-t border-slate-100 flex justify-between items-center">
              <div className="h-4 bg-slate-200 rounded-sm w-24" />
              <div className="h-8 bg-slate-200 rounded-lg w-20" />
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (type === 'stat') {
    return (
      <div className={`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 ${className}`}>
        {Array.from({ length: count }).map((_, idx) => (
          <div key={idx} className="bg-white p-5 rounded-xl border border-slate-200 animate-pulse flex items-center gap-4">
            <div className="w-12 h-12 rounded-xl bg-slate-200 shrink-0" />
            <div className="flex-1 space-y-2">
              <div className="h-3 bg-slate-200 rounded-sm w-1/2" />
              <div className="h-6 bg-slate-300 rounded-sm w-3/4" />
            </div>
          </div>
        ))}
      </div>
    );
  }

  // Default: table rows
  return (
    <div className={`w-full divide-y divide-slate-100 bg-white ${className}`}>
      {Array.from({ length: count }).map((_, idx) => (
        <div key={idx} className="p-4 flex items-center justify-between gap-4 animate-pulse">
          <div className="flex items-center gap-3 w-1/3">
            <div className="w-8 h-8 rounded-lg bg-slate-200 shrink-0" />
            <div className="space-y-2 flex-1">
              <div className="h-4 bg-slate-200 rounded-sm w-3/4" />
              <div className="h-3 bg-slate-100 rounded-sm w-1/2" />
            </div>
          </div>
          <div className="h-4 bg-slate-200 rounded-sm w-24 hidden sm:block" />
          <div className="h-6 bg-slate-200 rounded-full w-20" />
          <div className="h-4 bg-slate-200 rounded-sm w-28 hidden md:block" />
          <div className="h-8 bg-slate-200 rounded-lg w-16" />
        </div>
      ))}
    </div>
  );
}

export default SkeletonLoader;

