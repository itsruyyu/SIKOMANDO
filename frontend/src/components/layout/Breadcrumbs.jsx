import React from 'react';
import { Link } from 'react-router-dom';
import { ChevronRightIcon, HomeIcon } from '@heroicons/react/20/solid';

/**
 * Standard Breadcrumbs navigation
 */
export function Breadcrumbs({ items = [] }) {
  if (!items || items.length === 0) return null;

  return (
    <nav className="flex items-center text-xs text-slate-500 py-2 select-none" aria-label="Breadcrumb">
      <ol className="flex items-center space-x-2">
        <li>
          <Link to="/dashboard" className="text-slate-400 hover:text-blue-600 transition flex items-center">
            <HomeIcon className="w-3.5 h-3.5" />
            <span className="sr-only">Dashboard</span>
          </Link>
        </li>
        {items.map((item, index) => {
          const isLast = index === items.length - 1;
          return (
            <li key={index} className="flex items-center space-x-2">
              <ChevronRightIcon className="w-3 h-3 text-slate-300 shrink-0" />
              {isLast || !item.to ? (
                <span className="font-semibold text-slate-800">{item.label}</span>
              ) : (
                <Link to={item.to} className="hover:text-blue-600 transition">
                  {item.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}

export default Breadcrumbs;

